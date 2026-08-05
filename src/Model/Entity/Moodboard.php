<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Moodboard Entity
 *
 * @property int $id
 * @property string $titre
 * @property string $slug
 * @property string|null $description
 * @property string $theme
 * @property string $visibilite
 * @property string $share_token
 * @property string|null $password_hash
 * @property \Cake\I18n\DateTime|null $expires_at
 * @property int|null $user_id
 * @property int|null $destinataire_id
 * @property bool $commentaires_actifs
 * @property int $vues
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Destinataire $destinataire
 * @property \App\Model\Entity\MoodboardCommentaire[] $moodboard_commentaires
 * @property \App\Model\Entity\Photo[] $photos
 */
class Moodboard extends Entity
{
    use PartageableTrait;

    /**
     * @var list<string>
     */
    protected array $_hidden = ['password_hash'];

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
        'titre' => true,
        'slug' => true,
        'description' => true,
        'theme' => true,
        'visibilite' => true,
        'share_token' => true,
        'password_hash' => false,
        'expires_at' => true,
        'user_id' => true,
        'destinataire_id' => true,
        'commentaires_actifs' => true,
        'vues' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'destinataire' => true,
        'moodboard_commentaires' => true,
        'photos' => true,
    ];
}
