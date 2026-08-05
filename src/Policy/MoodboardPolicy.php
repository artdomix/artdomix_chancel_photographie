<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Moodboard;
use Authorization\IdentityInterface;

/**
 * Droits sur les moodboards.
 *
 * Ces règles concernent les visiteurs **connectés**. L'accès par lien de partage
 * (`/m/{token}`) ne passe pas par ici : il repose sur le jeton et, le cas
 * échéant, sur le mot de passe du moodboard, sans compte utilisateur.
 */
class MoodboardPolicy
{
    /**
     * Un membre voit les moodboards qui lui sont adressés ; l'admin voit tout.
     *
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Moodboard $moodboard Moodboard visé.
     * @return bool
     */
    public function canView(IdentityInterface $identite, Moodboard $moodboard): bool
    {
        if ($this->estAdmin($identite)) {
            return true;
        }

        if ($moodboard->visibilite === 'public') {
            return true;
        }

        return (int)$moodboard->destinataire_id === (int)$identite->getIdentifier();
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Moodboard $moodboard Moodboard visé.
     * @return bool
     */
    public function canAdd(IdentityInterface $identite, Moodboard $moodboard): bool
    {
        return $this->estAdmin($identite);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Moodboard $moodboard Moodboard visé.
     * @return bool
     */
    public function canEdit(IdentityInterface $identite, Moodboard $moodboard): bool
    {
        return $this->estAdmin($identite);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Moodboard $moodboard Moodboard visé.
     * @return bool
     */
    public function canDelete(IdentityInterface $identite, Moodboard $moodboard): bool
    {
        return $this->estAdmin($identite);
    }

    /**
     * Commenter suppose déjà le droit de voir, et que l'auteur ait laissé les
     * commentaires ouverts.
     *
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Moodboard $moodboard Moodboard visé.
     * @return bool
     */
    public function canCommenter(IdentityInterface $identite, Moodboard $moodboard): bool
    {
        return $moodboard->commentaires_actifs && $this->canView($identite, $moodboard);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @return bool
     */
    protected function estAdmin(IdentityInterface $identite): bool
    {
        return $identite->getOriginalData()->get('role') === 'admin';
    }
}
