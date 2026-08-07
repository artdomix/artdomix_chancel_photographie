<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\Photo;
use App\Service\Image\VarianteImage;
use Cake\View\Helper;

/**
 * Rend les photos en `<picture>` avec les formats et tailles adaptés.
 *
 * Le navigateur choisit lui-même le meilleur format disponible : AVIF s'il sait
 * le lire, sinon WebP, sinon JPEG. Les `<source>` ne sont écrites que pour les
 * formats réellement présents sur le disque — d'où les drapeaux `has_avif` et
 * `has_webp` portés par chaque photo, l'AVIF n'étant pas garanti chez
 * l'hébergeur.
 */
class PhotoHelper extends Helper
{
    /**
     * @var array<string>
     */
    protected array $helpers = ['Url'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'baseUrl' => '/media/photos/',
    ];

    /**
     * Rend une photo complète.
     *
     * @param \App\Model\Entity\Photo $photo Photo à afficher.
     * @param string $variante Variante de base (thumb, grid, content, large).
     * @param array<string, mixed> $options Options : class, sizes, lazy, lightbox.
     * @return string
     */
    public function image(Photo $photo, string $variante = 'grid', array $options = []): string
    {
        $options += [
            'class' => '',
            'sizes' => '100vw',
            'lazy' => true,
            'lightbox' => false,
        ];

        $variantes = VarianteImage::toutes();
        $largeur = $variantes[$variante]->largeurMax ?? 640;
        $ratio = $this->ratio($photo);

        $attributs = [
            'src' => $this->url($photo, $variante, 'jpeg'),
            'alt' => (string)($photo->alt ?? $photo->titre ?? ''),
            'width' => $largeur,
            'height' => (int)round($largeur / $ratio),
            'class' => $options['class'],
        ];

        if ($options['lazy']) {
            $attributs['loading'] = 'lazy';
            $attributs['decoding'] = 'async';
        } else {
            // L'image d'en-tête ne doit pas être différée : c'est elle que
            // mesure le Largest Contentful Paint.
            $attributs['fetchpriority'] = 'high';
        }

        // La couleur dominante en fond évite le rectangle noir pendant le
        // chargement, et le LQIP donne un aperçu flou immédiat.
        $styles = [];

        if ($photo->couleur_dominante) {
            $styles[] = 'background-color:' . $photo->couleur_dominante;
        }

        if ($photo->lqip) {
            $styles[] = sprintf(
                "background-image:url('%s');background-size:cover;background-position:center",
                $photo->lqip,
            );
        }

        if ($styles !== []) {
            $attributs['style'] = implode(';', $styles);
        }

        if ($options['lightbox']) {
            $attributs['data-lightbox-src'] = $this->url($photo, 'large', 'jpeg');
        }

        $sources = '';

        foreach ($this->formatsDisponibles($photo) as $format) {
            $sources .= sprintf(
                '<source type="image/%s" srcset="%s" sizes="%s">',
                $format,
                h($this->srcset($photo, $format)),
                h((string)$options['sizes']),
            );
        }

        return sprintf('<picture>%s<img%s></picture>', $sources, $this->attributs($attributs));
    }

    /**
     * Vignette cliquable, prête pour la lightbox GSAP.
     *
     * @param \App\Model\Entity\Photo $photo Photo à afficher.
     * @param array<string, mixed> $options Options passées à image().
     * @return string
     */
    public function vignette(Photo $photo, array $options = []): string
    {
        $legende = (string)($photo->titre ?? '');

        // La lightbox sert à parcourir, la fiche à s'arrêter : elle porte le
        // texte, les métadonnées et l'adresse partageable. Sans ce lien, la page
        // photo n'était atteignable que depuis un moteur de recherche.
        $serie = (string)($options['serie'] ?? '');
        $fiche = '/photo/' . $photo->slug . ($serie !== '' ? '?serie=' . rawurlencode($serie) : '');
        unset($options['serie']);

        return sprintf(
            '<button type="button" class="group block w-full overflow-hidden" '
            . 'data-lightbox data-lightbox-legende="%s" data-lightbox-fiche="%s" '
            . 'data-anim-item aria-label="%s">%s</button>',
            h($legende),
            h($this->Url->build($fiche)),
            h(__('Agrandir : {0}', $legende !== '' ? $legende : __('photo'))),
            $this->image($photo, 'grid', $options + [
                'class' => 'w-full transition-transform duration-700 ease-out group-hover:scale-105',
                'lightbox' => true,
            ]),
        );
    }

    /**
     * URL d'une déclinaison.
     *
     * @param \App\Model\Entity\Photo $photo Photo concernée.
     * @param string $variante Nom de la variante.
     * @param string $format Extension.
     * @return string
     */
    public function url(Photo $photo, string $variante = 'grid', string $format = 'jpeg'): string
    {
        return $this->getConfig('baseUrl') . $photo->fichier . '-' . $variante . '.' . $format;
    }

    /**
     * Construit le `srcset` en listant les variantes avec leur largeur réelle.
     *
     * @param \App\Model\Entity\Photo $photo Photo concernée.
     * @param string $format Format visé.
     * @return string
     */
    protected function srcset(Photo $photo, string $format): string
    {
        $entrees = [];

        foreach (VarianteImage::toutes() as $variante) {
            // Inutile de proposer une variante plus large que l'original : elle
            // n'a pas été générée, l'agrandissement étant refusé en amont.
            $largeur = min($variante->largeurMax, (int)($photo->largeur ?: $variante->largeurMax));

            $entrees[] = sprintf('%s %dw', $this->url($photo, $variante->nom, $format), $largeur);
        }

        return implode(', ', $entrees);
    }

    /**
     * @param \App\Model\Entity\Photo $photo Photo concernée.
     * @return list<string>
     */
    protected function formatsDisponibles(Photo $photo): array
    {
        $formats = [];

        if ($photo->has_avif) {
            $formats[] = 'avif';
        }

        if ($photo->has_webp) {
            $formats[] = 'webp';
        }

        return $formats;
    }

    /**
     * Ratio largeur/hauteur, avec un repli en 3:2 quand les dimensions manquent.
     *
     * @param \App\Model\Entity\Photo $photo Photo concernée.
     * @return float
     */
    protected function ratio(Photo $photo): float
    {
        if (!$photo->largeur || !$photo->hauteur) {
            return 1.5;
        }

        return $photo->largeur / $photo->hauteur;
    }

    /**
     * @param array<string, mixed> $attributs Paires attribut/valeur.
     * @return string
     */
    protected function attributs(array $attributs): string
    {
        $rendu = '';

        foreach ($attributs as $nom => $valeur) {
            if ($valeur === '' || $valeur === null) {
                continue;
            }

            $rendu .= sprintf(' %s="%s"', $nom, h((string)$valeur));
        }

        return $rendu;
    }
}
