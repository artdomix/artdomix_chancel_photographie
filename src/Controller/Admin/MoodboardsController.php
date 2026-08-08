<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\Role;
use App\Model\Enum\ThemeMoodboard;
use App\Model\Enum\Visibilite;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Création et gestion des moodboards.
 */
class MoodboardsController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $moodboards = $this->paginate(
            $this->fetchTable('Moodboards')->find()
                ->contain(['Destinataires'])
                ->orderBy(['Moodboards.created' => 'DESC']),
            ['limit' => 30],
        );

        $this->set(compact('moodboards'));
        $this->set('apercus', $this->apercus($moodboards));
        $this->set('title', 'Moodboards');
    }

    /**
     * Première photo de chaque moodboard, pour donner un repère visuel dans la liste.
     *
     * Un moodboard n'a pas de photo de couverture en base — contrairement aux
     * albums — parce que le rendu public part de la première photo de la
     * sélection. La liste montre donc la même image que la page partagée.
     *
     * @param iterable<\App\Model\Entity\Moodboard> $moodboards Moodboards affichés.
     * @return array<int, \App\Model\Entity\Photo>
     */
    protected function apercus(iterable $moodboards): array
    {
        $ids = [];

        foreach ($moodboards as $moodboard) {
            $ids[] = $moodboard->id;
        }

        if ($ids === []) {
            return [];
        }

        $apercus = [];

        // Une seule requête pour toute la page : on garde la première ligne
        // rencontrée par moodboard, l'ordre du tri faisant le reste.
        $liens = $this->fetchTable('MoodboardsPhotos')->find()
            ->contain(['Photos'])
            ->where(['MoodboardsPhotos.moodboard_id IN' => $ids])
            ->orderBy(['MoodboardsPhotos.moodboard_id' => 'ASC', 'MoodboardsPhotos.ordre' => 'ASC']);

        foreach ($liens as $lien) {
            $apercus[$lien->moodboard_id] ??= $lien->photo;
        }

        return $apercus;
    }

    /**
     * @param string|null $id Identifiant du moodboard, ou null pour une création.
     * @return \Cake\Http\Response|null
     */
    public function modifier(?string $id = null): ?Response
    {
        $moodboards = $this->fetchTable('Moodboards');

        $moodboard = $id === null
            ? $moodboards->newEmptyEntity()
            : $moodboards->find()
                ->where(['Moodboards.id' => (int)$id])
                ->contain(['Photos' => fn($q) => $q->orderBy(['MoodboardsPhotos.ordre' => 'ASC'])])
                ->first();

        if ($moodboard === null) {
            throw new NotFoundException();
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            $donnees = $this->request->getData();

            // Un champ mot de passe laissé vide signifie « ne pas changer », pas
            // « retirer la protection » : sans ce retrait, chaque enregistrement
            // déverrouillerait le moodboard à l'insu du photographe.
            if (($donnees['password'] ?? '') === '') {
                unset($donnees['password']);
            }

            $moodboards->patchEntity($moodboard, $donnees, [
                'associated' => ['Photos' => ['onlyIds' => true]],
            ]);

            if ($moodboards->save($moodboard)) {
                $this->Flash->success(__('Moodboard enregistré.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__("Le moodboard n'a pas pu être enregistré."));
        }

        $this->set('moodboard', $moodboard);
        // Listes construites depuis les enums plutôt que recopiées : ajouter un
        // cas suffit à le faire apparaître dans le formulaire.
        $this->set('themes', ThemeMoodboard::options());
        $this->set('visibilites', Visibilite::options());
        $this->set('membres', $this->fetchTable('Users')->find('list', valueField: 'email')
            ->where(['role' => Role::Membre])->toArray());
        $this->set('title', $id === null ? 'Nouveau moodboard' : 'Modifier un moodboard');

        return null;
    }

    /**
     * Régénère le jeton, ce qui invalide tous les liens déjà diffusés.
     *
     * @param string|null $id Identifiant du moodboard.
     * @return \Cake\Http\Response|null
     */
    public function revoquer(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        $moodboards = $this->fetchTable('Moodboards');
        $moodboard = $moodboards->find()->where(['id' => (int)$id])->first();

        if ($moodboard === null) {
            throw new NotFoundException();
        }

        $moodboards->revoquerLien($moodboard);
        $this->Flash->success(__('Nouveau lien généré. Les anciens liens ne fonctionnent plus.'));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @param string|null $id Identifiant du moodboard.
     * @return \Cake\Http\Response|null
     */
    public function supprimer(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $table = $this->fetchTable('Moodboards');
        $entite = $table->find()->where(['Moodboards.id' => (int)$id])->first();

        if ($entite === null) {
            throw new NotFoundException();
        }

        if ($table->delete($entite)) {
            $this->Flash->success(__('Moodboard supprimé.'));
        } else {
            $this->Flash->error(__("Le moodboard n'a pas pu être supprimé."));
        }

        return $this->redirect(['action' => 'index']);
    }
}
