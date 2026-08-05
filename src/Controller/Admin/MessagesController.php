<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Messages reçus par le formulaire de contact.
 *
 * Consultation seule, plus les deux bascules « lu » et « traité » : rien ici ne
 * doit permettre de modifier le texte d'un message. Ce que le visiteur a écrit
 * est une pièce, pas un contenu éditorial.
 */
class MessagesController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $messages = $this->fetchTable('Messages');
        $filtre = (string)$this->request->getQuery('filtre', 'tous');

        $requete = $messages->find()
            ->contain(['Demandes'])
            ->orderBy(['Messages.created' => 'DESC']);

        if ($filtre === 'non-lus') {
            $requete->where(['Messages.lu' => false]);
        } elseif ($filtre === 'a-traiter') {
            $requete->where(['Messages.traite' => false]);
        }

        $this->set('messages', $this->paginate($requete, ['limit' => 30]));
        $this->set('nbNonLus', $messages->find()->where(['lu' => false])->count());
        $this->set(compact('filtre'));
        $this->set('title', 'Messages');
    }

    /**
     * Ouvre un message et le marque lu au passage.
     *
     * @param string|null $id Identifiant du message.
     * @return void
     */
    public function voir(?string $id = null): void
    {
        $messages = $this->fetchTable('Messages');
        $message = $messages->find()
            ->where(['Messages.id' => (int)$id])
            ->contain(['Demandes'])
            ->first();

        if ($message === null) {
            throw new NotFoundException();
        }

        // Marquage en SQL direct : passer par save() toucherait `modified` et
        // ferait croire que le message a été édité.
        if (!$message->lu) {
            $messages->updateAll(['lu' => true], ['id' => $message->id]);
            $message->lu = true;
        }

        $this->set(compact('message'));
        $this->set('title', $message->sujet ?: 'Message de ' . $message->nom);
    }

    /**
     * Bascule l'état « traité ».
     *
     * @param string|null $id Identifiant du message.
     * @return \Cake\Http\Response|null
     */
    public function basculerTraite(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        $messages = $this->fetchTable('Messages');
        $message = $messages->find()->where(['id' => (int)$id])->first();

        if ($message === null) {
            throw new NotFoundException();
        }

        $messages->updateAll(['traite' => !$message->traite], ['id' => $message->id]);

        $this->Flash->success($message->traite
            ? __('Message rouvert.')
            : __('Message marqué comme traité.'));

        return $this->redirect($this->referer(['action' => 'index'], true));
    }

    /**
     * @param string|null $id Identifiant du message.
     * @return \Cake\Http\Response|null
     */
    public function supprimer(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        $messages = $this->fetchTable('Messages');
        $message = $messages->find()->where(['id' => (int)$id])->first();

        if ($message === null) {
            throw new NotFoundException();
        }

        if ($messages->delete($message)) {
            $this->Flash->success(__('Message supprimé.'));
        } else {
            $this->Flash->error(__("Le message n'a pas pu être supprimé."));
        }

        return $this->redirect(['action' => 'index']);
    }
}
