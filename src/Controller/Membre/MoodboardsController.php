<?php
declare(strict_types=1);

namespace App\Controller\Membre;

use App\Model\Entity\Moodboard;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Moodboards adressés au membre connecté.
 *
 * Le tableau de bord renvoyait jusqu'ici vers `/m/{jeton}`, c'est-à-dire le
 * chemin public — qui redemande le mot de passe d'un moodboard privé. Or le
 * destinataire est identifié : son compte suffit, et `MoodboardPolicy` est là
 * pour le dire.
 *
 * L'accès par lien reste servi par `App\Controller\MoodboardsController`, pour
 * les destinataires qui n'ont pas de compte.
 */
class MoodboardsController extends AppController
{
    /**
     * @param string|null $id Identifiant du moodboard.
     * @return void
     */
    public function voir(?string $id = null): void
    {
        $moodboard = $this->chargerMoodboard($id);

        $this->Authorization->authorize($moodboard, 'view');

        // Compteur en SQL direct : un `save()` toucherait `modified` et ferait
        // croire que le moodboard vient d'être édité.
        $this->fetchTable('Moodboards')->updateAll(['vues = vues + 1'], ['id' => $moodboard->id]);

        $this->set('moodboard', $moodboard);
        $this->set('commentaire', $this->fetchTable('MoodboardCommentaires')->newEmptyEntity());
        $this->set('peutCommenter', $this->Authorization->can($moodboard, 'commenter'));
        $this->set('title', $moodboard->titre);
    }

    /**
     * @param string|null $id Identifiant du moodboard.
     * @return \Cake\Http\Response|null
     */
    public function commenter(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        $moodboard = $this->chargerMoodboard($id);

        // `commenter` recouvre déjà le droit de voir, et vérifie en plus que le
        // photographe a laissé les retours ouverts.
        $this->Authorization->authorize($moodboard, 'commenter');

        $commentaires = $this->fetchTable('MoodboardCommentaires');
        $commentaire = $commentaires->newEntity($this->request->getData());
        $commentaire->moodboard_id = $moodboard->id;
        $commentaire->user_id = $this->Authentication->getIdentity()->getIdentifier();

        // Modération a priori, comme partout ailleurs sur le site : un retour
        // n'apparaît qu'une fois relu.
        $commentaire->valide = false;

        if ($commentaires->save($commentaire)) {
            $this->Flash->success(__('Merci, votre retour a bien été transmis.'));
        } else {
            $this->Flash->error(__("Votre retour n'a pas pu être enregistré."));
        }

        return $this->redirect(['action' => 'voir', $moodboard->id]);
    }

    /**
     * @param string|null $id Identifiant du moodboard.
     * @return \App\Model\Entity\Moodboard
     */
    protected function chargerMoodboard(?string $id): Moodboard
    {
        /** @var \App\Model\Entity\Moodboard|null $moodboard */
        $moodboard = $this->fetchTable('Moodboards')->find()
            ->where(['Moodboards.id' => (int)$id])
            ->contain([
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
}
