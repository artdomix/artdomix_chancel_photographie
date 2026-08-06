<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\Message;
use Cake\Mailer\Mailer;

/**
 * Courriels du formulaire de contact.
 *
 * Les envois étaient jusqu'ici construits à la main dans le contrôleur, en texte
 * brut concaténé. Les regrouper ici sort la mise en forme du contrôleur, permet
 * d'avoir une version HTML, et rend les envois testables sans passer par une
 * requête HTTP complète.
 */
class ContactMailer extends Mailer
{
    /**
     * Prévient le photographe qu'un message est arrivé.
     *
     * @param \App\Model\Entity\Message $message Message reçu.
     * @param string $destinataire Adresse du photographe.
     * @return void
     */
    public function notification(Message $message, string $destinataire): void
    {
        $this->setTo($destinataire)
            // L'expéditeur reste le domaine du site : mettre l'adresse du
            // visiteur ferait échouer SPF/DKIM et finirait en indésirables.
            // C'est `Reply-To` qui permet de répondre au visiteur d'un clic.
            ->setReplyTo($message->email, $message->nom)
            ->setSubject(__('Nouveau message : {0}', $message->sujet ?: 'sans objet'))
            ->setEmailFormat('both')
            ->setViewVars(['message' => $message])
            ->viewBuilder()
            ->setTemplate('contact_notification');
    }

    /**
     * Accuse réception auprès du visiteur.
     *
     * Un formulaire qui ne confirme rien laisse le doute : le visiteur ne sait
     * pas si son message est parti, et beaucoup le renvoient.
     *
     * @param \App\Model\Entity\Message $message Message reçu.
     * @param string $repondreA Adresse à laquelle le visiteur peut répondre.
     * @return void
     */
    public function accuseReception(Message $message, string $repondreA): void
    {
        $this->setTo($message->email, $message->nom)
            ->setReplyTo($repondreA)
            ->setSubject(__('Votre message a bien été reçu'))
            ->setEmailFormat('both')
            ->setViewVars(['message' => $message])
            ->viewBuilder()
            ->setTemplate('contact_accuse');
    }
}
