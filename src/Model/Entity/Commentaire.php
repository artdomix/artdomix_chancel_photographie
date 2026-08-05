<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Commentaire Entity
 *
 * @property int $id
 * @property int $article_id
 * @property int|null $user_id
 * @property string|null $auteur
 * @property string|null $email
 * @property string $contenu
 * @property bool $valide
 * @property string|null $ip
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Article $article
 * @property \App\Model\Entity\User $user
 */
class Commentaire extends Entity
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
        'article_id' => true,
        'user_id' => true,
        'auteur' => true,
        'email' => true,
        'contenu' => true,
        'valide' => true,
        'ip' => true,
        'created' => true,
        'modified' => true,
        'article' => true,
        'user' => true,
    ];
}
