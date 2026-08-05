<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Moodboard;
use App\Model\Table\MoodboardsTable;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Consultation publique d'un moodboard par son lien de partage.
 *
 * Cet accès ne passe **pas** par les comptes utilisateurs : le photographe envoie
 * une URL à un client qui n'a pas de compte et n'en veut pas. La sécurité repose
 * donc entièrement sur le jeton opaque et, pour un moodboard privé, sur un mot de
 * passe propre au moodboard.
 *
 * @property \App\Model\Table\MoodboardsTable $Moodboards
 */
class MoodboardsController extends AppController
{
    /**
     * Clé de session mémorisant les moodboards déverrouillés.
     */
    protected const SESSION_DEVERROUILLES = 'Moodboards.deverrouilles';

    /**
     * Déclarée explicitement : depuis PHP 8.2, affecter une propriété non
     * déclarée émet une dépréciation.
     *
     * @var \App\Model\Table\MoodboardsTable
     */
    protected MoodboardsTable $Moodboards;

    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Moodboards = $this->fetchTable('Moodboards');
        $this->autoriserPublic(['partage', 'deverrouiller', 'commenter']);
    }

    /**
     * Affiche un moodboard partagé.
     *
     * @param string|null $token Jeton de partage.
     * @return \Cake\Http\Response|null
     */
    public function partage(?string $token = null): ?Response
    {
        $moodboard = $this->chargerParToken($token);

        if ($moodboard->estProtege() && !$this->estDeverrouille($moodboard)) {
            $this->set('moodboard', $moodboard);
            $this->set('title', 'Moodboard protégé');
            $this->viewBuilder()->setLayout('partage');

            return $this->render('mot_de_passe');
        }

        // Compteur en SQL direct : passer par save() toucherait `modified` et
        // ferait croire que le moodboard vient d'être édité.
        $this->Moodboards->updateAll(['vues = vues + 1'], ['id' => $moodboard->id]);

        $this->set('moodboard', $moodboard);
        $this->set('commentaire', $this->Moodboards->MoodboardCommentaires->newEmptyEntity());
        $this->set('title', $moodboard->titre);
        $this->viewBuilder()->setLayout('partage');

        return null;
    }

    /**
     * Vérifie le mot de passe d'un moodboard privé.
     *
     * @param string|null $token Jeton de partage.
     * @return \Cake\Http\Response|null
     */
    public function deverrouiller(?string $token = null): ?Response
    {
        $this->request->allowMethod('post');

        $moodboard = $this->chargerParToken($token);

        if ($moodboard->verifierMotDePasse((string)$this->request->getData('password'))) {
            $deverrouilles = $this->request->getSession()->read(self::SESSION_DEVERROUILLES, []);
            $deverrouilles[$moodboard->id] = true;
            $this->request->getSession()->write(self::SESSION_DEVERROUILLES, $deverrouilles);

            return $this->redirect(['action' => 'partage', $token]);
        }

        // Délai volontaire : rend le forçage de mot de passe coûteux sans gêner
        // le visiteur légitime, qui ne se trompe qu'une fois ou deux.
        usleep(400_000);

        $this->Flash->error(__('Mot de passe incorrect.'));

        return $this->redirect(['action' => 'partage', $token]);
    }

    /**
     * Dépôt d'un commentaire sur un moodboard partagé.
     *
     * @param string|null $token Jeton de partage.
     * @return \Cake\Http\Response|null
     */
    public function commenter(?string $token = null): ?Response
    {
        $this->request->allowMethod('post');

        $moodboard = $this->chargerParToken($token);

        if (!$moodboard->commentaires_actifs) {
            throw new NotFoundException();
        }

        // Un moodboard protégé n'accepte de commentaire que d'un visiteur ayant
        // franchi le mot de passe : sans ce contrôle, l'URL de dépôt serait un
        // contournement de la protection.
        if ($moodboard->estProtege() && !$this->estDeverrouille($moodboard)) {
            throw new NotFoundException();
        }

        $commentaires = $this->Moodboards->MoodboardCommentaires;
        $commentaire = $commentaires->newEntity($this->request->getData());
        $commentaire->moodboard_id = $moodboard->id;
        $commentaire->user_id = $this->Authentication->getIdentity()?->getIdentifier();
        // Modération a priori, comme sur le blog.
        $commentaire->valide = false;

        if ($commentaires->save($commentaire)) {
            $this->Flash->success(__('Merci, votre retour a bien été transmis.'));
        } else {
            $this->Flash->error(__("Le commentaire n'a pas pu être enregistré."));
        }

        return $this->redirect(['action' => 'partage', $token]);
    }

    /**
     * Charge un moodboard depuis son jeton, ou échoue en 404.
     *
     * Le 404 est délibéré : un 403 confirmerait au visiteur que le jeton existe.
     * Un lien expiré est traité de la même façon.
     *
     * @param string|null $token Jeton de partage.
     * @return \App\Model\Entity\Moodboard
     */
    protected function chargerParToken(?string $token): Moodboard
    {
        if ($token === null || $token === '') {
            throw new NotFoundException();
        }

        $moodboard = $this->Moodboards->find()
            ->where(['Moodboards.share_token' => $token])
            ->contain([
                // Le tri porte sur la table de liaison : c'est l'ordre choisi
                // dans ce moodboard précis, pas un ordre global des photos.
                // Les données de liaison (note, mise en avant) restent
                // accessibles via `$photo->_joinData`.
                'Photos' => fn($q) => $q->orderBy(['MoodboardsPhotos.ordre' => 'ASC']),
                'MoodboardCommentaires' => fn($q) => $q
                    ->where(['MoodboardCommentaires.valide' => true])
                    ->orderBy(['MoodboardCommentaires.created' => 'ASC']),
            ])
            ->first();

        if ($moodboard === null || $moodboard->estExpire()) {
            throw new NotFoundException();
        }

        return $moodboard;
    }

    /**
     * @param \App\Model\Entity\Moodboard $moodboard Moodboard concerné.
     * @return bool
     */
    protected function estDeverrouille(Moodboard $moodboard): bool
    {
        $deverrouilles = $this->request->getSession()->read(self::SESSION_DEVERROUILLES, []);

        return !empty($deverrouilles[$moodboard->id]);
    }
}
