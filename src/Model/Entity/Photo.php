<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Photo Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $titre
 * @property string $slug
 * @property string|null $description
 * @property string|null $alt
 * @property string $fichier
 * @property string|null $extension_origine
 * @property int|null $largeur
 * @property int|null $hauteur
 * @property int|null $poids_octets
 * @property string|null $couleur_dominante
 * @property string|null $lqip
 * @property bool $has_avif
 * @property bool $has_webp
 * @property bool $filigrane
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Exif $exif
 * @property \App\Model\Entity\Article[] $articles
 * @property \App\Model\Entity\Exposition[] $expositions
 * @property \App\Model\Entity\GalerieCommentaire[] $galerie_commentaires
 * @property \App\Model\Entity\GalerieFavori[] $galerie_favoris
 * @property \App\Model\Entity\Livre[] $livres
 * @property \App\Model\Entity\Modele[] $modeles
 * @property \App\Model\Entity\MoodboardCommentaire[] $moodboard_commentaires
 * @property \App\Model\Entity\Shooting[] $shootings
 * @property \App\Model\Entity\Tirage[] $tirages
 * @property \App\Model\Entity\Video[] $videos
 * @property \App\Model\Entity\Album[] $albums
 * @property \App\Model\Entity\Galerie[] $galeries
 * @property \App\Model\Entity\Moodboard[] $moodboards
 * @property \App\Model\Entity\Tag[] $tags
 */
class Photo extends Entity
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
        'uuid' => true,
        'titre' => true,
        'slug' => true,
        'description' => true,
        'alt' => true,
        'fichier' => true,
        'extension_origine' => true,
        'largeur' => true,
        'hauteur' => true,
        'poids_octets' => true,
        'couleur_dominante' => true,
        'lqip' => true,
        'has_avif' => true,
        'has_webp' => true,
        'filigrane' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'exif' => true,
        'articles' => true,
        'expositions' => true,
        'galerie_commentaires' => true,
        'galerie_favoris' => true,
        'livres' => true,
        'modeles' => true,
        'moodboard_commentaires' => true,
        'shootings' => true,
        'tirages' => true,
        'videos' => true,
        'albums' => true,
        'galeries' => true,
        'moodboards' => true,
        'tags' => true,
    ];
}
