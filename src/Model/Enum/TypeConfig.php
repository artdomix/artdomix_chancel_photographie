<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Type d'un réglage, qui détermine le champ présenté dans l'admin.
 */
enum TypeConfig: string implements EnumLabelInterface
{
    case Texte = 'texte';
    case Nombre = 'nombre';
    case Booleen = 'booleen';
    case Html = 'html';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Texte => __('Texte'),
            self::Nombre => __('Nombre'),
            self::Booleen => __('Oui / non'),
            self::Html => __('Texte enrichi'),
        };
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
