<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * GalerieFavori Entity
 *
 * @property int $id
 * @property int $galerie_id
 * @property int $photo_id
 * @property int|null $user_id
 * @property string|null $session_key
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Galerie $galerie
 * @property \App\Model\Entity\Photo $photo
 * @property \App\Model\Entity\User $user
 */
class GalerieFavori extends Entity
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
        'user_id' => true,
        'session_key' => true,
        'created' => true,
        'modified' => true,
        'galerie' => true,
        'photo' => true,
        'user' => true,
    ];
}
