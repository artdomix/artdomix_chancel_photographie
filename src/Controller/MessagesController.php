<?php
declare(strict_types=1);

namespace App\Controller;

use App\Mailer\ContactMailer;
use App\Model\Entity\Message;
use App\Model\Table\MessagesTable;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Closure;
use Throwable;

/**
 * Formulaire de contact public.
 *
 * @property \App\Model\Table\MessagesTable $Messages
 */
class MessagesController extends AppController
{
    /**
     * Destinataire des notifications, à défaut de réglage en base.
     *
     * C'est cette valeur qui sert en production : la table `config` n'est
     * alimentée que par le seed de démonstration, qui n'y tourne jamais. Le
     * photographe peut la remplacer sans toucher au code, depuis
     * Administration → Réglages, clé `site.email`.
     */
    protected const EMAIL_PAR_DEFAUT = 'dodo15@msn.com';

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
                // La nature de la demande figure dans la notification : sans ce
                // chargement, l'entité fraîchement enregistrée ne la porte pas.
                $this->Messages->loadInto($message, ['Demandes']);
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
        $destinataire = $this->reglage('site.email', self::EMAIL_PAR_DEFAUT);

        // Le message est déjà en base et consultable depuis l'admin : un échec
        // d'envoi ne doit pas être remonté au visiteur, qui renverrait son
        // message pour rien. Il est journalisé, c'est tout.
        $this->envoyer(
            fn() => (new ContactMailer())->send('notification', [$message, $destinataire]),
            'Notification de contact',
        );

        // L'accusé de réception part séparément : si la notification échoue, le
        // visiteur doit quand même être rassuré, et réciproquement.
        //
        // Il part vers une adresse saisie par un inconnu : c'est le captcha
        // monté dans `beforeFilter` qui empêche d'en faire un relais à courriels
        // non sollicités. Le retirer transformerait ce formulaire en arme.
        $this->envoyer(
            fn() => (new ContactMailer())->send('accuseReception', [$message, $destinataire]),
            'Accusé de réception de contact',
        );
    }

    /**
     * Exécute un envoi en journalisant son échec plutôt qu'en le propageant.
     *
     * @param \Closure $envoi Envoi à tenter.
     * @param string $quoi Libellé pour le journal.
     * @return void
     */
    protected function envoyer(Closure $envoi, string $quoi): void
    {
        try {
            $envoi();
        } catch (Throwable $e) {
            $this->log(sprintf('%s non envoyé : %s', $quoi, $e->getMessage()), 'warning');
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
