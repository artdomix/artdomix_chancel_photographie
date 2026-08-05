<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MoodboardsPhoto Entity
 *
 * @property int $id
 * @property int $moodboard_id
 * @property int $photo_id
 * @property int $ordre
 * @property string|null $note
 * @property bool $mise_en_avant
 *
 * @property \App\Model\Entity\Moodboard $moodboard
 * @property \App\Model\Entity\Photo $photo
 */
class MoodboardsPhoto extends Entity
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
        'photo_id' => true,
        'ordre' => true,
        'note' => true,
        'mise_en_avant' => true,
        'moodboard' => true,
        'photo' => true,
    ];
}
