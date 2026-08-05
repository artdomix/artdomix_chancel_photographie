<?php
declare(strict_types=1);

namespace App\Service\Image;

use App\Model\Entity\Photo;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Enchaîne les étapes d'un import de photo : valider, ranger l'original,
 * produire les dérivés, lire les EXIF, enregistrer.
 *
 * Cette classe existe pour que le contrôleur d'administration reste court.
 * L'ancien `Admin/PhotosController::add()` faisait tout lui-même sur 90 lignes,
 * entrecoupées de `print_r()` : impossible à relire et impossible à tester.
 */
class PhotoUploader
{
    use LocatorAwareTrait;

    /**
     * @param string $dossierOriginaux Dossier des originaux, hors webroot.
     * @param \App\Service\Image\DerivativeGenerator $generateur Générateur de dérivés.
     * @param \App\Service\Image\ExifReader $exif Lecteur EXIF.
     * @param \App\Service\Image\UploadValidator $validateur Validateur d'upload.
     */
    public function __construct(
        protected string $dossierOriginaux,
        protected DerivativeGenerator $generateur,
        protected ExifReader $exif,
        protected UploadValidator $validateur,
    ) {
    }

    /**
     * Importe un fichier téléversé.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $fichier Fichier reçu.
     * @param array<string, mixed> $donnees Champs complémentaires (titre, albums, tags…).
     * @return \App\Service\Image\ResultatImport
     */
    public function importer(UploadedFileInterface $fichier, array $donnees = []): ResultatImport
    {
        $erreurs = $this->validateur->valider($fichier);

        if ($erreurs !== []) {
            return ResultatImport::echec($erreurs);
        }

        $uuid = Text::uuid();
        // Le nom de fichier vient d'un UUID, jamais du nom fourni par le client :
        // celui-ci peut contenir n'importe quoi (`../`, un point-virgule, un nom
        // déjà pris). L'ancien site hachait le nom d'origine puis bouclait pour
        // résoudre les collisions ; ici il n'y en a pas.
        $nomBase = str_replace('-', '', $uuid);
        $extension = $this->validateur->extension($fichier);

        $cheminOriginal = $this->rangerOriginal($fichier, $nomBase, $extension);

        try {
            $derives = $this->generateur->generer($cheminOriginal, $nomBase);
        } catch (RuntimeException $e) {
            // Si les dérivés échouent, l'original ne doit pas rester orphelin.
            $this->supprimerFichier($cheminOriginal);

            return ResultatImport::echec([$e->getMessage()]);
        }

        $photo = $this->enregistrer($uuid, $nomBase, $extension, $fichier, $derives, $cheminOriginal, $donnees);

        if ($photo === null) {
            $this->generateur->supprimer($nomBase);
            $this->supprimerFichier($cheminOriginal);

            return ResultatImport::echec([__("L'enregistrement de la photo a échoué.")]);
        }

        return ResultatImport::succes($photo);
    }

    /**
     * Supprime une photo, ses dérivés et son original.
     *
     * @param \App\Model\Entity\Photo $photo Photo à supprimer.
     * @return bool
     */
    public function supprimer(Photo $photo): bool
    {
        $photos = $this->fetchTable('Photos');

        if (!$photos->delete($photo)) {
            return false;
        }

        // Les fichiers ne sont effacés qu'une fois la ligne réellement supprimée :
        // dans l'ordre inverse, un échec en base laisserait une photo sans images.
        $this->generateur->supprimer($photo->fichier);
        $this->supprimerFichier($this->cheminOriginal($photo->fichier, (string)$photo->extension_origine));

        return true;
    }

    /**
     * Régénère les dérivés d'une photo existante, depuis son original.
     *
     * Utile après un changement de réglage (filigrane activé, nouvelle taille)
     * ou si l'hébergeur gagne le support de l'AVIF.
     *
     * @param \App\Model\Entity\Photo $photo Photo concernée.
     * @return bool
     */
    public function regenerer(Photo $photo): bool
    {
        $original = $this->cheminOriginal($photo->fichier, (string)$photo->extension_origine);

        if (!is_file($original)) {
            return false;
        }

        $this->generateur->supprimer($photo->fichier);
        $derives = $this->generateur->generer($original, $photo->fichier);

        $photo->largeur = $derives->largeur;
        $photo->hauteur = $derives->hauteur;
        $photo->lqip = $derives->lqip;
        $photo->couleur_dominante = $derives->couleurDominante;
        $photo->has_avif = $derives->avif;
        $photo->has_webp = $derives->webp;

        return (bool)$this->fetchTable('Photos')->save($photo);
    }

    /**
     * @param string $nomBase Nom de base.
     * @param string $extension Extension de l'original.
     * @return string
     */
    public function cheminOriginal(string $nomBase, string $extension): string
    {
        return $this->dossierOriginaux . DIRECTORY_SEPARATOR . $nomBase . '.' . $extension;
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $fichier Fichier reçu.
     * @param string $nomBase Nom de base.
     * @param string $extension Extension.
     * @return string Chemin de l'original rangé.
     */
    protected function rangerOriginal(UploadedFileInterface $fichier, string $nomBase, string $extension): string
    {
        if (!is_dir($this->dossierOriginaux)) {
            mkdir($this->dossierOriginaux, 0755, true);
        }

        $chemin = $this->cheminOriginal($nomBase, $extension);
        $fichier->moveTo($chemin);

        return $chemin;
    }

    /**
     * @param string $uuid Identifiant public.
     * @param string $nomBase Nom de base des fichiers.
     * @param string $extension Extension de l'original.
     * @param \Psr\Http\Message\UploadedFileInterface $fichier Fichier reçu.
     * @param \App\Service\Image\ResultatGeneration $derives Dérivés produits.
     * @param string $cheminOriginal Chemin de l'original.
     * @param array<string, mixed> $donnees Champs complémentaires.
     * @return \App\Model\Entity\Photo|null
     */
    protected function enregistrer(
        string $uuid,
        string $nomBase,
        string $extension,
        UploadedFileInterface $fichier,
        ResultatGeneration $derives,
        string $cheminOriginal,
        array $donnees,
    ): ?Photo {
        $photos = $this->fetchTable('Photos');

        // Le nom d'origine sert de titre par défaut : c'est souvent le seul
        // repère du photographe juste après un import en masse.
        $titre = $donnees['titre'] ?? null;

        if ($titre === null || trim((string)$titre) === '') {
            $titre = pathinfo((string)$fichier->getClientFilename(), PATHINFO_FILENAME) ?: null;
        }

        $photo = $photos->newEntity([
            'titre' => $titre,
            'description' => $donnees['description'] ?? null,
            'alt' => $donnees['alt'] ?? $titre,
            'actif' => $donnees['actif'] ?? true,
        ]);

        $photo->uuid = $uuid;
        $photo->fichier = $nomBase;
        $photo->extension_origine = $extension;
        $photo->largeur = $derives->largeur;
        $photo->hauteur = $derives->hauteur;
        $photo->poids_octets = (int)filesize($cheminOriginal);
        $photo->lqip = $derives->lqip;
        $photo->couleur_dominante = $derives->couleurDominante;
        $photo->has_avif = $derives->avif;
        $photo->has_webp = $derives->webp;

        $donneesExif = $this->exif->lire($cheminOriginal);

        if ($donneesExif !== []) {
            $photo->exif = $photos->Exifs->newEntity($donneesExif);
        }

        $sauvegarde = $photos->save($photo, ['associated' => ['Exifs']]);

        return $sauvegarde === false ? null : $photo;
    }

    /**
     * @param string $chemin Fichier à supprimer.
     * @return void
     */
    protected function supprimerFichier(string $chemin): void
    {
        if (is_file($chemin)) {
            unlink($chemin);
        }
    }
}
