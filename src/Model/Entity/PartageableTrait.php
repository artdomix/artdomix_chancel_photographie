<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\DateTime;

/**
 * Comportement commun aux moodboards et aux galeries client : protection par mot
 * de passe et date d'expiration.
 *
 * Le mot de passe d'un lien partagé est traité exactement comme un mot de passe
 * de compte — hashé, jamais comparé en clair, jamais sérialisé. Il protège
 * parfois des photos de clients : le niveau d'exigence est le même.
 */
trait PartageableTrait
{
    /**
     * Hache le mot de passe dès son affectation.
     *
     * Le champ virtuel `password` alimente `password_hash` : le formulaire
     * d'administration manipule un mot de passe en clair, la base ne voit
     * jamais que l'empreinte.
     *
     * @param string|null $valeur Mot de passe en clair, ou null pour retirer la protection.
     * @return string|null
     */
    protected function _setPassword(?string $valeur): ?string
    {
        $this->set('password_hash', $valeur === null || $valeur === ''
            ? null
            : (new DefaultPasswordHasher())->hash($valeur));

        return null;
    }

    /**
     * @return bool
     */
    public function estProtege(): bool
    {
        return !empty($this->password_hash);
    }

    /**
     * Vérifie un mot de passe saisi.
     *
     * @param string $saisi Mot de passe proposé.
     * @return bool
     */
    public function verifierMotDePasse(string $saisi): bool
    {
        if (!$this->estProtege()) {
            return true;
        }

        return (new DefaultPasswordHasher())->check($saisi, (string)$this->password_hash);
    }

    /**
     * @return bool
     */
    public function estExpire(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->lessThan(new DateTime());
    }

    /**
     * Un lien n'ouvre l'accès que si la ressource n'a pas expiré.
     *
     * @return bool
     */
    public function lienValide(): bool
    {
        return !$this->estExpire();
    }
}
