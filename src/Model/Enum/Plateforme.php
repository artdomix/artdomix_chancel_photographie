<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Plateforme d'hébergement d'une vidéo.
 *
 * On stocke la plateforme et un identifiant plutôt qu'une URL complète : c'est
 * l'application qui compose l'iframe, ce qui évite d'injecter dans la page une
 * URL saisie à la main.
 */
enum Plateforme: string implements EnumLabelInterface
{
    case Youtube = 'youtube';
    case Vimeo = 'vimeo';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Youtube => 'YouTube',
            self::Vimeo => 'Vimeo',
        };
    }

    /**
     * URL d'intégration, construite à partir de l'identifiant seul.
     *
     * @param string $reference Identifiant de la vidéo sur la plateforme.
     * @return string
     */
    public function urlEmbed(string $reference): string
    {
        // `rawurlencode` : la référence vient de l'admin, mais rien ne garantit
        // qu'elle ne contienne pas un caractère qui casserait l'URL.
        $reference = rawurlencode($reference);

        return match ($this) {
            self::Youtube => 'https://www.youtube-nocookie.com/embed/' . $reference,
            self::Vimeo => 'https://player.vimeo.com/video/' . $reference,
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
