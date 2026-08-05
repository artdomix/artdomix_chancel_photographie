<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * GaleriesPhoto Entity
 *
 * @property int $id
 * @property int $galerie_id
 * @property int $photo_id
 * @property int $ordre
 *
 * @property \App\Model\Entity\Galerie $galerie
 * @property \App\Model\Entity\Photo $photo
 */
class GaleriesPhoto extends Entity
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
        'galerie_id' => true,
        'photo_id' => true,
        'ordre' => true,
        'galerie' => true,
        'photo' => true,
    ];
}
