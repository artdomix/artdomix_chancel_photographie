<?php
declare(strict_types=1);

namespace App\Controller\Membre;

use App\Model\Entity\User;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Fiche du membre connecté : coordonnées et mot de passe.
 *
 * Sans cet écran, un client n'avait aucun moyen de corriger son téléphone ou de
 * changer son mot de passe autrement qu'en passant par « mot de passe oublié »,
 * c'est-à-dire par un courriel de réinitialisation — un détour absurde pour
 * quelqu'un qui est déjà connecté.
 *
 * `UserPolicy` autorise chacun sur son propre compte ; le rôle, lui, n'est
 * modifiable que depuis l'administration.
 */
class CompteController extends AppController
{
    /**
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $users = $this->fetchTable('Users');
        $moi = $this->compteCourant();

        $this->Authorization->authorize($moi, 'edit');

        if ($this->request->is(['post', 'put', 'patch'])) {
            // Liste blanche explicite : `patchEntity` sur les données brutes
            // laisserait passer tout ce qui est assignable en masse, dont
            // `actif`. Un membre n'a à changer que ses coordonnées.
            $users->patchEntity($moi, array_intersect_key($this->request->getData(), array_flip([
                'prenom', 'nom', 'societe', 'telephone',
            ])));

            if ($users->save($moi)) {
                $this->Flash->success(__('Vos informations ont été enregistrées.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__("Vos informations n'ont pas pu être enregistrées."));
        }

        $this->set('moi', $moi);
        $this->set('title', 'Mon compte');

        return null;
    }

    /**
     * Changement de mot de passe.
     *
     * L'ancien mot de passe est redemandé : sans cela, une session laissée
     * ouverte sur un poste partagé suffirait à verrouiller le compte de son
     * propriétaire.
     *
     * @return \Cake\Http\Response|null
     */
    public function motDePasse(): ?Response
    {
        $users = $this->fetchTable('Users');
        $moi = $this->compteCourant();

        $this->Authorization->authorize($moi, 'edit');

        if ($this->request->is(['post', 'put'])) {
            $actuel = (string)$this->request->getData('mot_de_passe_actuel');

            if (!$moi->verifierMotDePasse($actuel)) {
                // Même délai que sur le formulaire de connexion : sans lui, le
                // temps de réponse distinguerait un mot de passe faux d'un mot
                // de passe refusé pour une autre raison.
                usleep(400_000);
                $this->Flash->error(__('Le mot de passe actuel est incorrect.'));

                return $this->redirect(['action' => 'motDePasse']);
            }

            // Jeu de règles dédié : 12 caractères minimum, comme à la
            // réinitialisation.
            $users->patchEntity(
                $moi,
                ['password' => (string)$this->request->getData('password')],
                ['validate' => 'motDePasse'],
            );

            if ($users->save($moi)) {
                $this->Flash->success(__('Votre mot de passe a été changé.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__("Le mot de passe n'a pas pu être changé."));
        }

        $this->set('moi', $moi);
        $this->set('title', 'Changer mon mot de passe');

        return null;
    }

    /**
     * @return \App\Model\Entity\User
     */
    protected function compteCourant(): User
    {
        /** @var \App\Model\Entity\User|null $moi */
        $moi = $this->fetchTable('Users')->find()
            ->where(['Users.id' => (int)$this->Authentication->getIdentity()->getIdentifier()])
            ->first();

        if ($moi === null) {
            throw new NotFoundException();
        }

        return $moi;
    }
}
