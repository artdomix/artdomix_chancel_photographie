<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Modele Entity
 *
 * @property int $id
 * @property string $nom
 * @property string $slug
 * @property string|null $description
 * @property int|null $photo_id
 * @property string|null $instagram
 * @property string|null $site
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Photo $photo
 * @property \App\Model\Entity\Shooting[] $shootings
 */
class Modele extends Entity
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
        'nom' => true,
        'slug' => true,
        'description' => true,
        'photo_id' => true,
        'instagram' => true,
        'site' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'photo' => true,
        'shootings' => true,
    ];
}
