<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Message;
use App\Model\Table\MessagesTable;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Mailer\Mailer;
use Throwable;

/**
 * Formulaire de contact public.
 *
 * @property \App\Model\Table\MessagesTable $Messages
 */
class MessagesController extends AppController
{
    /**
     * Déclarée explicitement : depuis PHP 8.2, affecter une propriété non
     * déclarée émet une dépréciation.
     *
     * @var \App\Model\Table\MessagesTable
     */
    protected MessagesTable $Messages;

    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Messages = $this->fetchTable('Messages');
        $this->autoriserPublic(['contact']);

        // Captcha actif (petite opération) et passif (piège à robots) ajoutés au
        // vol : le modèle Messages reste utilisable sans captcha depuis l'admin,
        // où le contrôle n'aurait aucun sens.
        if (!$this->estConnecte()) {
            $this->Messages->addBehavior('Captcha.Captcha');
            $this->Messages->addBehavior('Captcha.PassiveCaptcha');
        }
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function contact(): ?Response
    {
        $message = $this->Messages->newEmptyEntity();

        if ($this->request->is('post')) {
            $message = $this->Messages->patchEntity($message, $this->request->getData());
            $message->ip = $this->request->clientIp();

            if ($this->Messages->save($message)) {
                $this->notifierPhotographe($message);
                $this->Flash->success(__('Merci, votre message a bien été envoyé.'));

                // Redirection après succès : sans elle, un rafraîchissement
                // renverrait le formulaire une seconde fois.
                return $this->redirect(['action' => 'contact']);
            }

            $this->Flash->error(__('Le message n\'a pas pu être envoyé, merci de vérifier le formulaire.'));
        }

        $demandes = $this->Messages->Demandes->find('list', keyField: 'id', valueField: 'nom')
            ->where(['actif' => true])
            ->orderBy(['ordre' => 'ASC'])
            ->toArray();

        $this->set(compact('message', 'demandes'));
        $this->set('title', 'Contact — Chancel Photographie');

        return null;
    }

    /**
     * @return bool
     */
    protected function estConnecte(): bool
    {
        return $this->Authentication->getIdentity() !== null;
    }

    /**
     * Prévient le photographe qu'un message est arrivé.
     *
     * L'échec d'envoi n'est pas remonté au visiteur : le message est déjà
     * enregistré en base et consultable depuis l'admin, lui dire que « ça n'a pas
     * marché » l'inciterait à renvoyer inutilement.
     *
     * @param \App\Model\Entity\Message $message Message reçu.
     * @return void
     */
    protected function notifierPhotographe(Message $message): void
    {
        $destinataire = $this->reglage('site.email', 'contact@chancel.art-domix.fr');

        try {
            (new Mailer('default'))
                ->setTo($destinataire)
                // L'expéditeur reste le domaine du site : mettre l'adresse du
                // visiteur ferait échouer SPF/DKIM et finirait en indésirables.
                ->setReplyTo($message->email, $message->nom)
                ->setSubject(__('Nouveau message : {0}', $message->sujet ?: 'sans objet'))
                ->deliver(sprintf(
                    "%s <%s>\nTéléphone : %s\n\n%s",
                    $message->nom,
                    $message->email,
                    $message->telephone ?: '—',
                    $message->contenu,
                ));
        } catch (Throwable $e) {
            $this->log(sprintf('Notification de contact non envoyée : %s', $e->getMessage()), 'warning');
        }
    }

    /**
     * @param string $cle Clé de réglage.
     * @param string $defaut Valeur par défaut.
     * @return string
     */
    protected function reglage(string $cle, string $defaut): string
    {
        $ligne = $this->fetchTable('Config')->find()->where(['cle' => $cle])->first();

        return $ligne?->valeur ?: $defaut;
    }
}
