<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Album Entity
 *
 * @property int $id
 * @property string $nom
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property int|null $lft
 * @property int|null $rght
 * @property int|null $cover_photo_id
 * @property \App\Model\Enum\VisibiliteAlbum $visibilite
 * @property int $ordre
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\ParentAlbum $parent_album
 * @property \App\Model\Entity\CoverPhoto $cover_photo
 * @property \App\Model\Entity\ChildAlbum[] $child_albums
 * @property \App\Model\Entity\Shooting[] $shootings
 * @property \App\Model\Entity\Photo[] $photos
 */
class Album extends Entity
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
        'parent_id' => true,
        'lft' => true,
        'rght' => true,
        'cover_photo_id' => true,
        'visibilite' => true,
        'ordre' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'parent_album' => true,
        'cover_photo' => true,
        'child_albums' => true,
        'shootings' => true,
        'photos' => true,
    ];
}
