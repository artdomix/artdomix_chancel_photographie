<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use App\Model\Enum\Role;
use Authorization\IdentityInterface;

/**
 * Droits sur les comptes.
 *
 * Un membre ne gère que son propre compte ; l'administrateur gère tout le monde.
 */
class UserPolicy
{
    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\User $user Compte visé.
     * @return bool
     */
    public function canView(IdentityInterface $identite, User $user): bool
    {
        return $this->estAdmin($identite) || $this->estLuiMeme($identite, $user);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\User $user Compte visé.
     * @return bool
     */
    public function canEdit(IdentityInterface $identite, User $user): bool
    {
        return $this->estAdmin($identite) || $this->estLuiMeme($identite, $user);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\User $user Compte visé.
     * @return bool
     */
    public function canAdd(IdentityInterface $identite, User $user): bool
    {
        return $this->estAdmin($identite);
    }

    /**
     * Un administrateur ne peut pas supprimer son propre compte : cela pourrait
     * laisser le site sans aucun accès d'administration.
     *
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\User $user Compte visé.
     * @return bool
     */
    public function canDelete(IdentityInterface $identite, User $user): bool
    {
        return $this->estAdmin($identite) && !$this->estLuiMeme($identite, $user);
    }

    /**
     * Seul un administrateur change les rôles — sans quoi n'importe quel membre
     * pourrait se promouvoir.
     *
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\User $user Compte visé.
     * @return bool
     */
    public function canChangerRole(IdentityInterface $identite, User $user): bool
    {
        return $this->estAdmin($identite) && !$this->estLuiMeme($identite, $user);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @return bool
     */
    protected function estAdmin(IdentityInterface $identite): bool
    {
        return $identite->getOriginalData()->get('role') === Role::Admin;
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\User $user Compte visé.
     * @return bool
     */
    protected function estLuiMeme(IdentityInterface $identite, User $user): bool
    {
        return (int)$identite->getIdentifier() === (int)$user->id;
    }
}
