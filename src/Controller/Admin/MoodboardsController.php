<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Création et gestion des moodboards.
 */
class MoodboardsController extends AppController
{
    /**
     * Thèmes proposés, alignés sur l'ENUM de la table et sur les modules JS.
     */
    protected const THEMES = [
        'mosaique-flip' => 'Mosaïque animée',
        'mur-parallaxe' => 'Mur en parallaxe',
        'carrousel-pinne' => 'Carrousel épinglé',
        'grille-cinetique' => 'Grille cinétique',
    ];

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
        $this->set('title', 'Moodboards');
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
        $this->set('themes', self::THEMES);
        $this->set('membres', $this->fetchTable('Users')->find('list', valueField: 'email')
            ->where(['role' => 'member'])->toArray());
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
}
