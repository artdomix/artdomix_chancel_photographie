<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Rôle d'un compte.
 *
 * Un enum plutôt qu'une chaîne libre : le typage empêche la faute de frappe
 * silencieuse (`'adminn'`) qui, dans une comparaison, refuserait simplement
 * l'accès sans que rien ne signale l'erreur.
 */
enum Role: string implements EnumLabelInterface
{
    case Admin = 'admin';
    case Membre = 'member';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Administrateur'),
            self::Membre => __('Membre'),
        };
    }

    /**
     * Liste prête pour un champ select.
     *
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
