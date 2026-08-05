<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Galerie Entity
 *
 * @property int $id
 * @property string $nom
 * @property string $slug
 * @property string|null $description
 * @property int|null $client_id
 * @property string $share_token
 * @property string|null $password_hash
 * @property \Cake\I18n\DateTime|null $expires_at
 * @property bool $telechargement_actif
 * @property int $quota_favoris
 * @property \Cake\I18n\Date|null $date_livraison
 * @property bool $actif
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Client $client
 * @property \App\Model\Entity\GalerieCommentaire[] $galerie_commentaires
 * @property \App\Model\Entity\GalerieFavori[] $galerie_favoris
 * @property \App\Model\Entity\Photo[] $photos
 */
class Galerie extends Entity
{
    use PartageableTrait;

    /**
     * @var list<string>
     */
    protected array $_hidden = ['password_hash'];

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
        'client_id' => true,
        'share_token' => true,
        // Champ virtuel du formulaire, qui alimente `password_hash` via
        // PartageableTrait. Sans cette ligne, patchEntity() écarte
        // silencieusement la saisie et la livraison reste sans protection.
        'password' => true,
        'password_hash' => false,
        'expires_at' => true,
        'telechargement_actif' => true,
        'quota_favoris' => true,
        'date_livraison' => true,
        'actif' => true,
        'created' => true,
        'modified' => true,
        'client' => true,
        'galerie_commentaires' => true,
        'galerie_favoris' => true,
        'photos' => true,
    ];
}
