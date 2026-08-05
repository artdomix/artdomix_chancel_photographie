<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MoodboardCommentaire Entity
 *
 * @property int $id
 * @property int $moodboard_id
 * @property int|null $user_id
 * @property int|null $photo_id
 * @property string|null $auteur
 * @property string $contenu
 * @property bool $valide
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Moodboard $moodboard
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Photo $photo
 */
class MoodboardCommentaire extends Entity
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
        'moodboard_id' => true,
        'user_id' => true,
        'photo_id' => true,
        'auteur' => true,
        'contenu' => true,
        'valide' => true,
        'created' => true,
        'modified' => true,
        'moodboard' => true,
        'user' => true,
        'photo' => true,
    ];
}
