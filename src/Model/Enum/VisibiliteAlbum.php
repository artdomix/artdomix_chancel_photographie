<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Visibilité d'un album.
 *
 * Volontairement distincte de `Visibilite` : un album n'a que deux états, et
 * réutiliser l'enum des moodboards ferait apparaître un cas « lien » qui n'a
 * aucun sens ici.
 */
enum VisibiliteAlbum: string implements EnumLabelInterface
{
    case Public = 'public';
    case Prive = 'prive';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Public => __('Public'),
            self::Prive => __('Privé'),
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
