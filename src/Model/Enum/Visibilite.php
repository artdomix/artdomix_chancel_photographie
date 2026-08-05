<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Visibilité d'un moodboard.
 *
 * `Lien` est le mode par défaut : accessible à qui possède le jeton, mais non
 * listé. C'est le compromis attendu pour un partage avec un client.
 */
enum Visibilite: string implements EnumLabelInterface
{
    case Prive = 'prive';
    case Lien = 'lien';
    case Public = 'public';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Prive => __('Privé (mot de passe)'),
            self::Lien => __('Accessible par lien'),
            self::Public => __('Public (listé)'),
        };
    }

    /**
     * Le contenu est-il référençable par un moteur de recherche ?
     *
     * @return bool
     */
    public function estIndexable(): bool
    {
        return $this === self::Public;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $cas) {
            $options[$cas->value] = $cas->label();
        }

        return $options;
    }
}
