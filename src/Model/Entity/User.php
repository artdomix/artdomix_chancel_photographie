<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string|null $prenom
 * @property string|null $nom
 * @property string|null $societe
 * @property string|null $telephone
 * @property bool $actif
 * @property string|null $token
 * @property \Cake\I18n\DateTime|null $token_expire
 * @property \Cake\I18n\DateTime|null $derniere_connexion
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Commentaire[] $commentaires
 * @property \App\Model\Entity\GalerieCommentaire[] $galerie_commentaires
 * @property \App\Model\Entity\GalerieFavori[] $galerie_favoris
 * @property \App\Model\Entity\MoodboardCommentaire[] $moodboard_commentaires
 * @property \App\Model\Entity\Moodboard[] $moodboards
 */
class User extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'email' => true,
        'password' => true,
        'role' => false,
        'prenom' => true,
        'nom' => true,
        'societe' => true,
        'telephone' => true,
        'actif' => true,
        'token' => true,
        'token_expire' => true,
        'derniere_connexion' => true,
        'created' => true,
        'modified' => true,
        'commentaires' => true,
        'galerie_commentaires' => true,
        'galerie_favoris' => true,
        'moodboard_commentaires' => true,
        'moodboards' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
        'token',
    ];

    /**
     * Hache le mot de passe dès son affectation.
     *
     * Placer le hachage ici plutôt que dans un contrôleur garantit qu'aucun
     * chemin de code ne peut enregistrer un mot de passe en clair.
     *
     * @param string|null $valeur Mot de passe en clair.
     * @return string|null
     */
    protected function _setPassword(?string $valeur): ?string
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        return (new DefaultPasswordHasher())->hash($valeur);
    }

    /**
     * Nom d'affichage, avec repli sur l'e-mail.
     *
     * @return string
     */
    protected function _getNomComplet(): string
    {
        $complet = trim(sprintf('%s %s', (string)$this->prenom, (string)$this->nom));

        return $complet !== '' ? $complet : (string)$this->email;
    }

    /**
     * @return bool
     */
    public function estAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
