<?php
declare(strict_types=1);

namespace App\Service\Image;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;

/**
 * Produit les déclinaisons d'une photo à partir de son original.
 *
 * Trois principes hérités des défauts de l'ancien site :
 *
 * 1. **L'original n'est jamais servi.** Il reste hors de `webroot/`, seules les
 *    variantes sont publiques. L'ancien site exposait le fichier d'origine.
 * 2. **Aucune sortie parasite.** L'ancien composant `ResizeImg` faisait des
 *    `echo` à chaque étape, qui polluaient le HTML avant les redirections. Ici
 *    la classe ne produit rien : elle renvoie un résultat.
 * 3. **Traitement synchrone, une photo par requête.** L'hébergement mutualisé
 *    n'a pas de worker ; découper par photo garde chaque requête courte sans
 *    imposer de file d'attente à administrer.
 */
class DerivativeGenerator
{
    /**
     * Formats de sortie, du plus efficace au plus compatible.
     */
    public const FORMATS = ['avif', 'webp', 'jpeg'];

    protected ImageManager $manager;

    /**
     * @param string $racineMedia Dossier public des dérivés (webroot/media/photos).
     * @param array<string, mixed> $options Options : filigrane, texte du filigrane.
     */
    public function __construct(
        protected string $racineMedia,
        protected array $options = [],
    ) {
        // Pilote GD explicitement : Imagick n'est pas garanti sur un mutualisé,
        // et un pilote choisi implicitement changerait de comportement selon
        // l'hébergeur.
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * L'AVIF dépend de la compilation de GD : présent sur PHP 8.4 en local, pas
     * forcément chez l'hébergeur. On teste plutôt que de supposer, et la photo
     * enregistre ce qui a réellement été écrit.
     *
     * @return bool
     */
    public static function supporteAvif(): bool
    {
        return function_exists('imageavif');
    }

    /**
     * @return bool
     */
    public static function supporteWebp(): bool
    {
        return function_exists('imagewebp');
    }

    /**
     * Génère toutes les variantes d'un original.
     *
     * @param string $cheminOriginal Chemin absolu du fichier source.
     * @param string $nomBase Nom de base des dérivés, sans extension.
     * @return \App\Service\Image\ResultatGeneration
     * @throws \RuntimeException Si l'original est illisible.
     */
    public function generer(string $cheminOriginal, string $nomBase): ResultatGeneration
    {
        if (!is_file($cheminOriginal)) {
            throw new RuntimeException(sprintf('Original introuvable : %s', $cheminOriginal));
        }

        $source = $this->manager->read($cheminOriginal);
        $largeur = $source->width();
        $hauteur = $source->height();

        $formats = $this->formatsDisponibles();
        $fichiers = [];

        foreach (VarianteImage::toutes() as $variante) {
            $image = $this->redimensionner($source, $variante);

            if ($variante->filigraneAutorise && $this->filigraneActif()) {
                $image = $this->appliquerFiligrane($image);
            }

            foreach ($formats as $format) {
                $chemin = $this->cheminVariante($nomBase, $variante->nom, $format);
                $this->ecrire($image, $chemin, $format, $variante->qualite);
                $fichiers[] = $chemin;
            }
        }

        return new ResultatGeneration(
            largeur: $largeur,
            hauteur: $hauteur,
            lqip: $this->genererLqip($source),
            couleurDominante: $this->couleurDominante($source),
            avif: in_array('avif', $formats, true),
            webp: in_array('webp', $formats, true),
            fichiers: $fichiers,
        );
    }

    /**
     * Supprime toutes les déclinaisons d'une photo.
     *
     * @param string $nomBase Nom de base des dérivés.
     * @return int Nombre de fichiers effectivement supprimés.
     */
    public function supprimer(string $nomBase): int
    {
        $supprimes = 0;

        foreach (VarianteImage::toutes() as $variante) {
            foreach (self::FORMATS as $format) {
                $chemin = $this->cheminVariante($nomBase, $variante->nom, $format);

                // `file_exists()` avant `unlink()` : l'ancien site s'en passait et
                // émettait un warning PHP dès qu'une déclinaison manquait.
                if (is_file($chemin) && unlink($chemin)) {
                    $supprimes++;
                }
            }
        }

        return $supprimes;
    }

    /**
     * Chemin absolu d'une déclinaison.
     *
     * @param string $nomBase Nom de base.
     * @param string $variante Nom de la variante.
     * @param string $format Extension.
     * @return string
     */
    public function cheminVariante(string $nomBase, string $variante, string $format): string
    {
        return $this->racineMedia . DIRECTORY_SEPARATOR . $nomBase . '-' . $variante . '.' . $format;
    }

    /**
     * @return list<string>
     */
    protected function formatsDisponibles(): array
    {
        $formats = ['jpeg'];

        if (self::supporteWebp()) {
            array_unshift($formats, 'webp');
        }

        if (self::supporteAvif()) {
            array_unshift($formats, 'avif');
        }

        return $formats;
    }

    /**
     * Redimensionne en préservant le ratio, sans jamais agrandir.
     *
     * Agrandir une petite image ne ferait qu'alourdir le fichier sans ajouter
     * de détail.
     *
     * @param \Intervention\Image\Interfaces\ImageInterface $source Image source.
     * @param \App\Service\Image\VarianteImage $variante Variante visée.
     * @return \Intervention\Image\Interfaces\ImageInterface
     */
    protected function redimensionner(ImageInterface $source, VarianteImage $variante): ImageInterface
    {
        $copie = clone $source;

        if ($copie->width() <= $variante->largeurMax) {
            return $copie;
        }

        return $copie->scale(width: $variante->largeurMax);
    }

    /**
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image à écrire.
     * @param string $chemin Destination.
     * @param string $format Format de sortie.
     * @param int $qualite Qualité d'encodage.
     * @return void
     */
    protected function ecrire(ImageInterface $image, string $chemin, string $format, int $qualite): void
    {
        $dossier = dirname($chemin);

        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        match ($format) {
            'avif' => $image->toAvif($qualite)->save($chemin),
            'webp' => $image->toWebp($qualite)->save($chemin),
            default => $image->toJpeg($qualite)->save($chemin),
        };
    }

    /**
     * Miniature encodée en base64, affichée floutée pendant le chargement.
     *
     * 24 px de large : quelques centaines d'octets, assez pour donner une idée
     * de l'image et éviter le rectangle vide pendant l'animation GSAP.
     *
     * @param \Intervention\Image\Interfaces\ImageInterface $source Image source.
     * @return string
     */
    protected function genererLqip(ImageInterface $source): string
    {
        $miniature = (clone $source)->scale(width: 24);

        return 'data:image/jpeg;base64,' . base64_encode((string)$miniature->toJpeg(40));
    }

    /**
     * Couleur moyenne de l'image, utilisée comme fond avant chargement.
     *
     * @param \Intervention\Image\Interfaces\ImageInterface $source Image source.
     * @return string Code hexadécimal.
     */
    protected function couleurDominante(ImageInterface $source): string
    {
        // Réduire à 1 px délègue la moyenne pondérée à GD : plus rapide et plus
        // fidèle qu'un échantillonnage manuel.
        $pixel = (clone $source)->scale(width: 1, height: 1)->pickColor(0, 0);

        return $pixel->toHex('#');
    }

    /**
     * @return bool
     */
    protected function filigraneActif(): bool
    {
        return (bool)($this->options['filigrane'] ?? false);
    }

    /**
     * Filigrane textuel en bas à droite.
     *
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image à marquer.
     * @return \Intervention\Image\Interfaces\ImageInterface
     */
    protected function appliquerFiligrane(ImageInterface $image): ImageInterface
    {
        $texte = (string)($this->options['filigrane_texte'] ?? '© Chancel');

        if ($texte === '') {
            return $image;
        }

        return $image->text($texte, $image->width() - 24, $image->height() - 24, function ($police): void {
            $police->size(max(14, (int)($this->options['filigrane_taille'] ?? 18)));
            $police->color('rgba(255, 255, 255, 0.55)');
            $police->align('right');
            $police->valign('bottom');
        });
    }
}
