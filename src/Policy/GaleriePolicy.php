<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Galerie;
use App\Model\Enum\Role;
use Authorization\IdentityInterface;

/**
 * Droits sur les galeries client.
 *
 * Une galerie livrée n'appartient qu'à un client : contrairement aux moodboards,
 * il n'existe pas de galerie « publique ». Un membre qui n'en est pas le
 * destinataire n'y a jamais accès, même connecté.
 */
class GaleriePolicy
{
    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Galerie $galerie Galerie visée.
     * @return bool
     */
    public function canView(IdentityInterface $identite, Galerie $galerie): bool
    {
        if ($this->estAdmin($identite)) {
            return true;
        }

        // Une galerie expirée cesse d'être consultable par le client, mais reste
        // visible de l'administrateur qui doit pouvoir la prolonger.
        if ($galerie->estExpire() || !$galerie->actif) {
            return false;
        }

        return (int)$galerie->client_id === (int)$identite->getIdentifier();
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Galerie $galerie Galerie visée.
     * @return bool
     */
    public function canAdd(IdentityInterface $identite, Galerie $galerie): bool
    {
        return $this->estAdmin($identite);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Galerie $galerie Galerie visée.
     * @return bool
     */
    public function canEdit(IdentityInterface $identite, Galerie $galerie): bool
    {
        return $this->estAdmin($identite);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Galerie $galerie Galerie visée.
     * @return bool
     */
    public function canDelete(IdentityInterface $identite, Galerie $galerie): bool
    {
        return $this->estAdmin($identite);
    }

    /**
     * Le client sélectionne ses favoris ; l'administrateur n'a pas à choisir à
     * sa place.
     *
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Galerie $galerie Galerie visée.
     * @return bool
     */
    public function canChoisirFavoris(IdentityInterface $identite, Galerie $galerie): bool
    {
        return !$galerie->estExpire()
            && $galerie->actif
            && (int)$galerie->client_id === (int)$identite->getIdentifier();
    }

    /**
     * Le téléchargement doit avoir été explicitement ouvert par le photographe :
     * c'est souvent ce qui distingue une livraison payée d'une simple épreuve.
     *
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @param \App\Model\Entity\Galerie $galerie Galerie visée.
     * @return bool
     */
    public function canTelecharger(IdentityInterface $identite, Galerie $galerie): bool
    {
        if ($this->estAdmin($identite)) {
            return true;
        }

        return $galerie->telechargement_actif && $this->canView($identite, $galerie);
    }

    /**
     * @param \Authorization\IdentityInterface $identite Utilisateur connecté.
     * @return bool
     */
    protected function estAdmin(IdentityInterface $identite): bool
    {
        return $identite->getOriginalData()->get('role') === Role::Admin;
    }
}
