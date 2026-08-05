<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Famille d'un tag, pour présenter la recherche par groupes plutôt qu'en une
 * liste indifférenciée.
 */
enum TypeTag: string implements EnumLabelInterface
{
    case Sujet = 'sujet';
    case Lieu = 'lieu';
    case Materiel = 'materiel';
    case Technique = 'technique';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Sujet => __('Sujet'),
            self::Lieu => __('Lieu'),
            self::Materiel => __('Matériel'),
            self::Technique => __('Technique'),
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
