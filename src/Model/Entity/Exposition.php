<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Exposition Entity
 *
 * @property int $id
 * @property string $titre
 * @property string $slug
 * @property string|null $description
 * @property string|null $lieu
 * @property int|null $photo_id
 * @property \Cake\I18n\Date|null $date_debut
 * @property \Cake\I18n\Date|null $date_fin
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Photo $photo
 */
class Exposition extends Entity
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
        'lieu' => true,
        'photo_id' => true,
        'date_debut' => true,
        'date_fin' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'photo' => true,
    ];
}
