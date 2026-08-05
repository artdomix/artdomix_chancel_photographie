<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AlbumsPhoto Entity
 *
 * @property int $id
 * @property int $album_id
 * @property int $photo_id
 * @property int $ordre
 *
 * @property \App\Model\Entity\Album $album
 * @property \App\Model\Entity\Photo $photo
 */
class AlbumsPhoto extends Entity
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
        'album_id' => true,
        'photo_id' => true,
        'ordre' => true,
        'album' => true,
        'photo' => true,
    ];
}
