<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Livre Entity
 *
 * @property int $id
 * @property string $titre
 * @property string $slug
 * @property string|null $description
 * @property int|null $photo_id
 * @property string|null $prix
 * @property string|null $lien_achat
 * @property \Cake\I18n\Date|null $date_parution
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Photo $photo
 */
class Livre extends Entity
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
        'photo_id' => true,
        'prix' => true,
        'lien_achat' => true,
        'date_parution' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'photo' => true,
    ];
}
