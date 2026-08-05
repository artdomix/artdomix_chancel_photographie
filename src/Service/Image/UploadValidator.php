<?php
declare(strict_types=1);

namespace App\Service\Image;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Contrôle un fichier téléversé avant tout traitement.
 *
 * L'ancien site faisait confiance à `$_FILES` : ni type MIME, ni extension, ni
 * taille n'étaient vérifiés, et le fichier atterrissait directement dans
 * `webroot/`. Un `.php` déposé par ce chemin devenait exécutable.
 *
 * Ici rien n'est cru sur parole : le type est déduit du **contenu** du fichier,
 * pas de l'en-tête envoyé par le navigateur ni de l'extension, tous deux
 * choisis par celui qui téléverse.
 */
class UploadValidator
{
    /**
     * Types réellement acceptés, associés à leur extension canonique.
     */
    protected const TYPES_ACCEPTES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/tiff' => 'tif',
    ];

    /**
     * @param int $tailleMax Taille maximale en octets (50 Mo par défaut : un RAW
     *   converti ou un JPEG plein format tient largement dedans).
     * @param int $pixelsMax Nombre total de pixels accepté. Garde-fou contre les
     *   « bombes de décompression » : une image annoncée en 60 000 × 60 000
     *   épuiserait la mémoire de PHP avant même le redimensionnement.
     */
    public function __construct(
        protected int $tailleMax = 50 * 1024 * 1024,
        protected int $pixelsMax = 80_000_000,
    ) {
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $fichier Fichier téléversé.
     * @return list<string> Liste des erreurs ; vide si le fichier est accepté.
     */
    public function valider(UploadedFileInterface $fichier): array
    {
        if ($fichier->getError() !== UPLOAD_ERR_OK) {
            return [$this->messageErreurPhp($fichier->getError())];
        }

        $taille = $fichier->getSize();

        if ($taille === null || $taille === 0) {
            return [__('Le fichier est vide.')];
        }

        if ($taille > $this->tailleMax) {
            return [__(
                'Le fichier dépasse la taille maximale de {0} Mo.',
                (int)round($this->tailleMax / 1024 / 1024),
            )];
        }

        $chemin = $fichier->getStream()->getMetadata('uri');

        if (!is_string($chemin) || !is_file($chemin)) {
            return [__('Fichier téléversé illisible.')];
        }

        // `getimagesize()` lit l'en-tête binaire : c'est le contenu qui décide,
        // pas le nom du fichier ni le type déclaré par le navigateur.
        $infos = @getimagesize($chemin);

        if ($infos === false) {
            return [__("Ce fichier n'est pas une image exploitable.")];
        }

        $mime = $infos['mime'] ?? '';

        if (!isset(self::TYPES_ACCEPTES[$mime])) {
            return [__('Format non accepté ({0}). Formats acceptés : JPEG, PNG, WebP, AVIF, TIFF.', $mime)];
        }

        if ($infos[0] * $infos[1] > $this->pixelsMax) {
            return [__("Les dimensions de l'image sont trop grandes.")];
        }

        return [];
    }

    /**
     * Extension canonique déduite du contenu.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $fichier Fichier téléversé.
     * @return string
     */
    public function extension(UploadedFileInterface $fichier): string
    {
        $chemin = $fichier->getStream()->getMetadata('uri');
        $infos = is_string($chemin) ? @getimagesize($chemin) : false;
        $mime = $infos === false ? '' : ($infos['mime'] ?? '');

        return self::TYPES_ACCEPTES[$mime] ?? 'jpg';
    }

    /**
     * @param int $code Code d'erreur PHP.
     * @return string
     */
    protected function messageErreurPhp(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __('Fichier trop volumineux pour le serveur.'),
            UPLOAD_ERR_PARTIAL => __('Le téléversement a été interrompu.'),
            UPLOAD_ERR_NO_FILE => __('Aucun fichier reçu.'),
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => __("Le serveur n'a pas pu écrire le fichier."),
            default => __('Téléversement refusé.'),
        };
    }
}
