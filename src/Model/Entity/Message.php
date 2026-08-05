<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Message Entity
 *
 * @property int $id
 * @property int|null $demande_id
 * @property string $nom
 * @property string $email
 * @property string|null $telephone
 * @property string|null $sujet
 * @property string $contenu
 * @property bool $lu
 * @property bool $traite
 * @property string|null $ip
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Demande $demande
 */
class Message extends Entity
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
        'demande_id' => true,
        'nom' => true,
        'email' => true,
        'telephone' => true,
        'sujet' => true,
        'contenu' => true,
        'lu' => true,
        'traite' => true,
        'ip' => true,
        'created' => true,
        'modified' => true,
        'demande' => true,
    ];
}
