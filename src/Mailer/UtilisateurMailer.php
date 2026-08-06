<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\Mailer\Mailer;

/**
 * Courriels liés aux comptes.
 */
class UtilisateurMailer extends Mailer
{
    /**
     * Lien de réinitialisation du mot de passe.
     *
     * @param \App\Model\Entity\User $utilisateur Destinataire.
     * @param string $lien URL absolue de réinitialisation.
     * @return void
     */
    public function reinitialisation(User $utilisateur, string $lien): void
    {
        $this->setTo($utilisateur->email, $utilisateur->nom_complet)
            ->setSubject(__('Réinitialisation de votre mot de passe'))
            ->setEmailFormat('both')
            ->setViewVars(['utilisateur' => $utilisateur, 'lien' => $lien])
            ->viewBuilder()
            ->setTemplate('reinitialisation');
    }
}
