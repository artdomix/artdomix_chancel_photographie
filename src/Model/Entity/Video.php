<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Video Entity
 *
 * @property int $id
 * @property string $titre
 * @property string $slug
 * @property string|null $description
 * @property int|null $typevideo_id
 * @property int|null $photo_id
 * @property string $plateforme
 * @property string $video_ref
 * @property int|null $duree
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Typevideo $typevideo
 * @property \App\Model\Entity\Photo $photo
 */
class Video extends Entity
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
        'typevideo_id' => true,
        'photo_id' => true,
        'plateforme' => true,
        'video_ref' => true,
        'duree' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'typevideo' => true,
        'photo' => true,
    ];
}
