<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Thème d'animation d'un moodboard.
 *
 * La valeur est aussi le nom du module JavaScript chargé côté navigateur :
 * ajouter un cas ici sans créer le module correspondant ferait retomber le
 * rendu sur le thème par défaut, sans erreur visible. Les deux vont de pair.
 */
enum ThemeMoodboard: string implements EnumLabelInterface
{
    case MosaiqueFlip = 'mosaique-flip';
    case MurParallaxe = 'mur-parallaxe';
    case CarrouselPinne = 'carrousel-pinne';
    case GrilleCinetique = 'grille-cinetique';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::MosaiqueFlip => __('Mosaïque animée'),
            self::MurParallaxe => __('Mur en parallaxe'),
            self::CarrouselPinne => __('Carrousel épinglé'),
            self::GrilleCinetique => __('Grille cinétique'),
        };
    }

    /**
     * Description affichée à l'admin au moment du choix.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::MosaiqueFlip => __('Les vignettes se déploient depuis le centre.'),
            self::MurParallaxe => __('Trois colonnes défilent à des vitesses différentes.'),
            self::CarrouselPinne => __('Défilement horizontal, section épinglée (bureau uniquement).'),
            self::GrilleCinetique => __('Révélation par masque et réaction au pointeur.'),
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
