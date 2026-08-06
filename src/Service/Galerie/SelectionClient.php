<?php
declare(strict_types=1);

namespace App\Service\Galerie;

use App\Model\Entity\Galerie;
use App\Model\Entity\Photo;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;
use ZipArchive;

/**
 * Sélection de photos par le client, et archive de sa livraison.
 *
 * Une galerie s'atteint par deux chemins : un lien de partage, où le visiteur
 * n'est identifié que par une clé de session, et l'espace membre, où c'est son
 * compte qui l'identifie. Les conditions d'entrée diffèrent — jeton et mot de
 * passe d'un côté, `GaleriePolicy` de l'autre — mais ce qui se passe ensuite est
 * rigoureusement identique.
 *
 * Ce service porte donc cette part commune. Les contrôleurs gardent ce qui les
 * distingue : comment ils vérifient qu'on a le droit d'être là.
 */
class SelectionClient
{
    use LocatorAwareTrait;

    /**
     * Identifiants des photos retenues par un visiteur.
     *
     * @param \App\Model\Entity\Galerie $galerie Galerie concernée.
     * @param array<string, mixed> $visiteur Identité : `user_id` ou `session_key`.
     * @return list<int>
     */
    public function photosRetenues(Galerie $galerie, array $visiteur): array
    {
        return $this->fetchTable('GalerieFavoris')->find()
            ->where(['galerie_id' => $galerie->id] + $visiteur)
            ->all()
            ->extract('photo_id')
            ->toList();
    }

    /**
     * Ajoute ou retire une photo de la sélection.
     *
     * @param \App\Model\Entity\Galerie $galerie Galerie concernée.
     * @param \App\Model\Entity\Photo $photo Photo visée.
     * @param array<string, mixed> $visiteur Identité : `user_id` ou `session_key`.
     * @return \App\Service\Galerie\ResultatSelection
     */
    public function basculer(Galerie $galerie, Photo $photo, array $visiteur): ResultatSelection
    {
        $favoris = $this->fetchTable('GalerieFavoris');
        $conditions = ['galerie_id' => $galerie->id, 'photo_id' => $photo->id] + $visiteur;

        $existant = $favoris->find()->where($conditions)->first();

        if ($existant !== null) {
            $favoris->delete($existant);

            return new ResultatSelection(false);
        }

        // Le quota est vérifié au moment de l'ajout, pas à l'affichage : entre
        // les deux, le client a pu retirer une photo dans un autre onglet.
        $atteint = $galerie->quota_favoris > 0
            && count($this->photosRetenues($galerie, $visiteur)) >= $galerie->quota_favoris;

        if ($atteint) {
            return new ResultatSelection(false, true);
        }

        $favoris->saveOrFail($favoris->newEntity($conditions));

        return new ResultatSelection(true);
    }

    /**
     * Vérifie qu'une photo appartient bien à la galerie.
     *
     * Sans ce contrôle, un identifiant arbitraire permettrait de marquer
     * n'importe quelle photo du site depuis n'importe quelle galerie.
     *
     * @param \App\Model\Entity\Galerie $galerie Galerie concernée.
     * @param int $photoId Identifiant soumis.
     * @return \App\Model\Entity\Photo|null
     */
    public function photoDeLaGalerie(Galerie $galerie, int $photoId): ?Photo
    {
        /** @var \App\Model\Entity\Photo|null $photo */
        $photo = $this->fetchTable('Photos')->find()
            ->matching('Galeries', fn($q) => $q->where(['Galeries.id' => $galerie->id]))
            ->where(['Photos.id' => $photoId])
            ->first();

        return $photo;
    }

    /**
     * Photos à livrer : la sélection du client, ou la galerie entière s'il n'a
     * rien retenu — une archive vide n'aiderait personne.
     *
     * @param \App\Model\Entity\Galerie $galerie Galerie concernée, photos chargées.
     * @param list<int> $retenues Identifiants retenus.
     * @return list<\App\Model\Entity\Photo>
     */
    public function photosALivrer(Galerie $galerie, array $retenues): array
    {
        if ($retenues === []) {
            return array_values($galerie->photos);
        }

        return array_values(array_filter(
            $galerie->photos,
            fn(Photo $photo) => in_array($photo->id, $retenues, true),
        ));
    }

    /**
     * Construit l'archive ZIP d'une livraison.
     *
     * Ce sont les dérivés `large` (2048 px) qui partent, jamais les originaux :
     * ceux-ci restent hors de `webroot` et ne sortent du serveur que par un
     * envoi manuel du photographe.
     *
     * @param \App\Model\Entity\Galerie $galerie Galerie concernée.
     * @param list<\App\Model\Entity\Photo> $photos Photos à inclure.
     * @return string|null Chemin de l'archive, ou null si rien n'a pu être ajouté.
     */
    public function archiver(Galerie $galerie, array $photos): ?string
    {
        if ($photos === []) {
            return null;
        }

        $archive = new ZipArchive();
        $chemin = TMP . 'galerie-' . $galerie->id . '-' . bin2hex(Security::randomBytes(8)) . '.zip';

        if ($archive->open($chemin, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $ajoutees = 0;

        foreach ($photos as $photo) {
            $source = WWW_ROOT . 'media' . DS . 'photos' . DS . $photo->fichier . '-large.jpeg';

            if (is_file($source)) {
                // Nom lisible dans l'archive plutôt que l'UUID interne : le
                // client doit pouvoir s'y retrouver.
                $archive->addFile($source, sprintf('%s-%s.jpg', $galerie->slug, $photo->slug));
                $ajoutees++;
            }
        }

        $archive->close();

        // Aucun dérivé sur le disque : mieux vaut ne rien livrer qu'une archive
        // vide que le client croirait complète.
        if ($ajoutees === 0) {
            if (is_file($chemin)) {
                unlink($chemin);
            }

            return null;
        }

        return $chemin;
    }
}
