<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\Photo;
use Cake\View\Helper;

/**
 * Métadonnées de partage et données structurées.
 *
 * Un portfolio de photographe vit du partage : un lien collé sur un réseau
 * social doit afficher une image, pas une vignette générique. Et les données
 * structurées permettent aux moteurs de comprendre qu'il s'agit de photographies
 * signées, avec leur auteur et leur licence.
 *
 * @property \App\View\Helper\PhotoHelper $Photo
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class SeoHelper extends Helper
{
    /**
     * @var array<string>
     */
    protected array $helpers = ['Url', 'Photo'];

    /**
     * Balises OpenGraph et Twitter.
     *
     * @param array<string, mixed> $options titre, description, image, type.
     * @return string
     */
    public function partage(array $options = []): string
    {
        $options += [
            'titre' => 'Chancel Photographie',
            'description' => 'Photographe en Seine-et-Marne. Portrait, voyage, automobile.',
            'image' => null,
            'type' => 'website',
        ];

        $balises = [
            ['property' => 'og:site_name', 'content' => 'Chancel Photographie'],
            ['property' => 'og:type', 'content' => $options['type']],
            ['property' => 'og:title', 'content' => $options['titre']],
            ['property' => 'og:description', 'content' => $options['description']],
            ['property' => 'og:url', 'content' => $this->Url->build($this->getView()->getRequest()->getRequestTarget(), ['fullBase' => true])],
            ['property' => 'og:locale', 'content' => 'fr_FR'],
        ];

        if ($options['image'] instanceof Photo) {
            // Format `content` (1200 px) : c'est la largeur attendue par les
            // aperçus de la plupart des réseaux.
            $url = $this->Url->build($this->Photo->url($options['image'], 'content', 'jpeg'), ['fullBase' => true]);

            $balises[] = ['property' => 'og:image', 'content' => $url];
            $balises[] = ['property' => 'og:image:alt', 'content' => (string)($options['image']->alt ?? '')];
            $balises[] = ['name' => 'twitter:card', 'content' => 'summary_large_image'];
        } else {
            $balises[] = ['name' => 'twitter:card', 'content' => 'summary'];
        }

        $rendu = '';

        foreach ($balises as $balise) {
            $cle = isset($balise['property']) ? 'property' : 'name';
            $rendu .= sprintf(
                '<meta %s="%s" content="%s">' . "\n",
                $cle,
                h($balise[$cle]),
                h((string)$balise['content']),
            );
        }

        return $rendu;
    }

    /**
     * Données structurées JSON-LD décrivant une photographie.
     *
     * @param \App\Model\Entity\Photo $photo Photo décrite.
     * @return string
     */
    public function photoJsonLd(Photo $photo): string
    {
        $donnees = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'name' => $photo->titre,
            'description' => $photo->description,
            'contentUrl' => $this->Url->build($this->Photo->url($photo, 'large', 'jpeg'), ['fullBase' => true]),
            'thumbnailUrl' => $this->Url->build($this->Photo->url($photo, 'thumb', 'jpeg'), ['fullBase' => true]),
            'width' => $photo->largeur,
            'height' => $photo->hauteur,
            'datePublished' => $photo->created?->format('c'),
            'creator' => [
                '@type' => 'Person',
                'name' => 'Chancel',
            ],
            'copyrightNotice' => $photo->exif->copyright ?? '© Chancel',
            // Signale explicitement que l'image n'est pas réutilisable librement.
            'acquireLicensePage' => $this->Url->build('/contact', ['fullBase' => true]),
        ], static fn($valeur): bool => $valeur !== null && $valeur !== '');

        return $this->script($donnees);
    }

    /**
     * Fil d'Ariane structuré.
     *
     * @param list<array{nom: string, url: string}> $etapes Étapes du fil.
     * @return string
     */
    public function filAriane(array $etapes): string
    {
        $elements = [];

        foreach ($etapes as $index => $etape) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $etape['nom'],
                'item' => $this->Url->build($etape['url'], ['fullBase' => true]),
            ];
        }

        return $this->script([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ]);
    }

    /**
     * @param array<string, mixed> $donnees Structure à sérialiser.
     * @return string
     */
    protected function script(array $donnees): string
    {
        // JSON_UNESCAPED_UNICODE garde les accents lisibles ; JSON_HEX_TAG évite
        // qu'une chaîne contenant `</script>` ne referme la balise.
        $json = json_encode(
            $donnees,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
        );

        return sprintf('<script type="application/ld+json">%s</script>', $json);
    }
}
