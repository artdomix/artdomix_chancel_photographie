<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Article Entity
 *
 * @property int $id
 * @property string $titre
 * @property string $slug
 * @property string|null $chapeau
 * @property string|null $contenu
 * @property int|null $photo_id
 * @property int|null $auteur_id
 * @property \Cake\I18n\DateTime|null $publie_le
 * @property bool $actif
 * @property int $vues
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Photo $photo
 * @property \App\Model\Entity\Auteur $auteur
 * @property \App\Model\Entity\Commentaire[] $commentaires
 */
class Article extends Entity
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
        'chapeau' => true,
        'contenu' => true,
        'photo_id' => true,
        'auteur_id' => true,
        'publie_le' => true,
        'actif' => true,
        'vues' => true,
        'created' => true,
        'modified' => true,
        'photo' => true,
        'auteur' => true,
        'commentaires' => true,
    ];
}
