<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Exif Entity
 *
 * @property int $id
 * @property int $photo_id
 * @property string|null $apn
 * @property string|null $objectif
 * @property string|null $ouverture
 * @property int|null $iso
 * @property string|null $exposition
 * @property string|null $focale
 * @property string|null $artiste
 * @property string|null $copyright
 * @property \Cake\I18n\DateTime|null $date_capture
 * @property string|null $gps_lat
 * @property string|null $gps_lng
 * @property string|null $gps_altitude
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Photo $photo
 */
class Exif extends Entity
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
        'apn' => true,
        'objectif' => true,
        'ouverture' => true,
        'iso' => true,
        'exposition' => true,
        'focale' => true,
        'artiste' => true,
        'copyright' => true,
        'date_capture' => true,
        'gps_lat' => true,
        'gps_lng' => true,
        'gps_altitude' => true,
        'created' => true,
        'modified' => true,
        'photo' => true,
    ];
}
