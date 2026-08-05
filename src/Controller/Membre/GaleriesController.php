<?php
declare(strict_types=1);

namespace App\Controller\Membre;

use Cake\Http\Exception\NotFoundException;

/**
 * Consultation des galeries livrées, côté membre connecté.
 *
 * Contrairement à l'accès par lien, chaque action passe ici par `GaleriePolicy` :
 * être connecté ne suffit pas, encore faut-il être le client destinataire. C'est
 * ce qui empêche un membre de lire la livraison d'un autre en changeant
 * l'identifiant dans l'URL.
 */
class GaleriesController extends AppController
{
    /**
     * @param string|null $id Identifiant de la galerie.
     * @return void
     */
    public function voir(?string $id = null): void
    {
        $galerie = $this->fetchTable('Galeries')->find()
            ->where(['Galeries.id' => (int)$id])
            ->contain(['Photos' => fn($q) => $q->orderBy(['GaleriesPhotos.ordre' => 'ASC'])])
            ->first();

        if ($galerie === null) {
            throw new NotFoundException();
        }

        // La policy tranche : admin, ou client destinataire d'une galerie
        // active et non expirée.
        $this->Authorization->authorize($galerie, 'view');

        $favoris = $this->fetchTable('GalerieFavoris')->find()
            ->where([
                'galerie_id' => $galerie->id,
                'user_id' => $this->Authentication->getIdentity()->getIdentifier(),
            ])
            ->all()
            ->extract('photo_id')
            ->toList();

        $this->set(compact('galerie', 'favoris'));
        $this->set('title', $galerie->nom);
    }
}
