<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Tirage Entity
 *
 * @property int $id
 * @property int $photo_id
 * @property int|null $typetirage_id
 * @property string|null $format
 * @property string|null $papier
 * @property string|null $prix
 * @property int|null $tirage_limite
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Photo $photo
 * @property \App\Model\Entity\Typetirage $typetirage
 */
class Tirage extends Entity
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
        'photo_id' => true,
        'typetirage_id' => true,
        'format' => true,
        'papier' => true,
        'prix' => true,
        'tirage_limite' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'photo' => true,
        'typetirage' => true,
    ];
}
