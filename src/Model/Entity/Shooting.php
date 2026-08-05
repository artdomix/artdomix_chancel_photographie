<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Shooting Entity
 *
 * @property int $id
 * @property string $titre
 * @property string $slug
 * @property string|null $description
 * @property int|null $modele_id
 * @property int|null $photo_id
 * @property int|null $album_id
 * @property \Cake\I18n\Date|null $date_shooting
 * @property string|null $lieu
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Modele $modele
 * @property \App\Model\Entity\Photo $photo
 * @property \App\Model\Entity\Album $album
 */
class Shooting extends Entity
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
        'titre' => true,
        'slug' => true,
        'description' => true,
        'modele_id' => true,
        'photo_id' => true,
        'album_id' => true,
        'date_shooting' => true,
        'lieu' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'modele' => true,
        'photo' => true,
        'album' => true,
    ];
}
