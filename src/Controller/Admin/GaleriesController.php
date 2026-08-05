<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\Role;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Gestion des galeries client (livraisons).
 *
 * Le pendant administrateur de `Membre\GaleriesController` : ici on compose la
 * livraison, là le client la consulte. Les deux zones partagent la même table
 * mais pas les mêmes droits — d'où deux contrôleurs plutôt qu'un seul truffé de
 * conditions.
 */
class GaleriesController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Le réordonnancement des photos envoie du JSON depuis le glisser-déposer.
        $this->actionsJavascript(['ordonner']);
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $galeries = $this->paginate(
            $this->fetchTable('Galeries')->find()
                ->contain(['Clients'])
                ->orderBy(['Galeries.created' => 'DESC']),
            ['limit' => 30],
        );

        $this->set(compact('galeries'));
        $this->set('title', 'Galeries client');
    }

    /**
     * @param string|null $id Identifiant de la galerie, ou null pour une création.
     * @return \Cake\Http\Response|null
     */
    public function modifier(?string $id = null): ?Response
    {
        $galeries = $this->fetchTable('Galeries');

        $galerie = $id === null
            ? $galeries->newEmptyEntity()
            : $galeries->find()
                ->where(['Galeries.id' => (int)$id])
                ->contain(['Photos' => fn($q) => $q->orderBy(['GaleriesPhotos.ordre' => 'ASC'])])
                ->first();

        if ($galerie === null) {
            throw new NotFoundException();
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            $donnees = $this->request->getData();

            // Champ mot de passe vide = « ne pas changer », pas « retirer la
            // protection ». Même raisonnement que pour les moodboards : sans ce
            // retrait, chaque enregistrement déverrouillerait la livraison.
            if (($donnees['password'] ?? '') === '') {
                unset($donnees['password']);
            }

            $galeries->patchEntity($galerie, $donnees, [
                'associated' => ['Photos' => ['onlyIds' => true]],
            ]);

            if ($galeries->save($galerie)) {
                $this->Flash->success(__('Galerie enregistrée.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__("La galerie n'a pas pu être enregistrée."));
        }

        $this->set('galerie', $galerie);
        $this->set('clients', $this->fetchTable('Users')->find('list', valueField: 'email')
            ->where(['role' => Role::Membre, 'actif' => true])->toArray());
        $this->set('title', $id === null ? 'Nouvelle galerie' : 'Modifier une galerie');

        return null;
    }

    /**
     * Enregistre le nouvel ordre des photos d'une galerie.
     *
     * @param string|null $id Identifiant de la galerie.
     * @return \Cake\Http\Response
     */
    public function ordonner(?string $id = null): Response
    {
        $this->request->allowMethod('post');

        $galerie = $this->fetchTable('Galeries')->find()->where(['id' => (int)$id])->first();

        if ($galerie === null) {
            throw new NotFoundException();
        }

        $ordre = (array)($this->request->getData('ordre') ?? []);
        $liaison = $this->fetchTable('GaleriesPhotos');

        foreach ($ordre as $position => $photoId) {
            $liaison->updateAll(
                ['ordre' => (int)$position],
                ['galerie_id' => $galerie->id, 'photo_id' => (int)$photoId],
            );
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['ok' => true, 'nb' => count($ordre)]));
    }

    /**
     * Sélection du client : les favoris qu'il a cochés, dans l'ordre de la
     * galerie. C'est la raison d'être du proofing — le photographe doit pouvoir
     * lire le résultat sans se connecter au compte du client.
     *
     * @param string|null $id Identifiant de la galerie.
     * @return void
     */
    public function favoris(?string $id = null): void
    {
        $galerie = $this->fetchTable('Galeries')->find()
            ->where(['Galeries.id' => (int)$id])
            ->contain(['Clients'])
            ->first();

        if ($galerie === null) {
            throw new NotFoundException();
        }

        // Deux jointures : l'une restreint aux photos cochées, l'autre ramène la
        // colonne `ordre` de la galerie. Le tri suit l'ordre de la livraison, pas
        // celui des clics — c'est ainsi que le photographe lit sa sélection.
        $photos = $this->fetchTable('Photos')->find()
            ->innerJoinWith('GalerieFavoris', fn($q) => $q->where(['GalerieFavoris.galerie_id' => $galerie->id]))
            ->innerJoinWith('Galeries', fn($q) => $q->where(['Galeries.id' => $galerie->id]))
            ->orderBy(['GaleriesPhotos.ordre' => 'ASC'])
            ->all();

        $this->set(compact('galerie', 'photos'));
        $this->set('title', 'Favoris — ' . $galerie->nom);
    }

    /**
     * Régénère le jeton, ce qui invalide tous les liens déjà diffusés.
     *
     * @param string|null $id Identifiant de la galerie.
     * @return \Cake\Http\Response|null
     */
    public function revoquer(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        $galeries = $this->fetchTable('Galeries');
        $galerie = $galeries->find()->where(['id' => (int)$id])->first();

        if ($galerie === null) {
            throw new NotFoundException();
        }

        $galeries->revoquerLien($galerie);
        $this->Flash->success(__('Nouveau lien généré. Les anciens liens ne fonctionnent plus.'));

        return $this->redirect(['action' => 'index']);
    }
}
