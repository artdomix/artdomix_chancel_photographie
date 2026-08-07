<?php
declare(strict_types=1);

namespace App\Service\Association;

use Cake\Http\Exception\NotFoundException;

/**
 * Description d'une liaison entre une entité et des photos.
 *
 * Trois tables de jonction relient des photos à autre chose — albums, moodboards,
 * galeries — et elles ne diffèrent que par trois choses : leur nom, leur clé
 * étrangère, et les colonnes qu'elles portent en plus de l'ordre. Les décrire
 * ici plutôt que d'écrire trois fois le même écran évite que la correction d'un
 * défaut n'en oublie deux.
 *
 * Ajouter une quatrième liaison se fait dans `self::TOUTES`, et nulle part
 * ailleurs.
 */
final class Liaison
{
    /**
     * Liaisons connues, indexées par le segment d'URL qui les désigne.
     *
     * @var array<string, array<string, mixed>>
     */
    private const TOUTES = [
        'album' => [
            'cible' => 'Albums',
            'jonction' => 'AlbumsPhotos',
            'cleEtrangere' => 'album_id',
            'libelle' => 'album',
            'champsAnnexes' => [],
            // Seuls les albums portent une photo de couverture.
            'couverture' => 'cover_photo_id',
            'controleur' => 'Albums',
        ],
        'moodboard' => [
            'cible' => 'Moodboards',
            'jonction' => 'MoodboardsPhotos',
            'cleEtrangere' => 'moodboard_id',
            'libelle' => 'moodboard',
            // La note et la mise en avant sont lues par le gabarit public : une
            // photo mise en avant occupe deux cases dans la mosaïque.
            'champsAnnexes' => ['note', 'mise_en_avant'],
            'couverture' => null,
            'controleur' => 'Moodboards',
        ],
        'galerie' => [
            'cible' => 'Galeries',
            'jonction' => 'GaleriesPhotos',
            'cleEtrangere' => 'galerie_id',
            'libelle' => 'galerie',
            'champsAnnexes' => [],
            'couverture' => null,
            'controleur' => 'Galeries',
        ],
    ];

    /**
     * @param string $type Segment d'URL désignant la liaison.
     * @param string $cible Nom du modèle porteur.
     * @param string $jonction Nom du modèle de jonction.
     * @param string $cleEtrangere Colonne de jonction pointant la cible.
     * @param string $libelle Libellé au singulier, pour les messages.
     * @param array<string> $champsAnnexes Colonnes éditables de la jonction.
     * @param string|null $couverture Colonne de photo de couverture, si elle existe.
     * @param string $controleur Contrôleur d'administration de la cible.
     */
    private function __construct(
        public readonly string $type,
        public readonly string $cible,
        public readonly string $jonction,
        public readonly string $cleEtrangere,
        public readonly string $libelle,
        public readonly array $champsAnnexes,
        public readonly ?string $couverture,
        public readonly string $controleur,
    ) {
    }

    /**
     * @param string|null $type Segment d'URL, par exemple « moodboard ».
     * @return self
     */
    public static function pour(?string $type): self
    {
        // Un type inconnu est une 404 et non une exception de programmation : il
        // vient de l'URL, donc de l'extérieur.
        if ($type === null || !isset(self::TOUTES[$type])) {
            throw new NotFoundException();
        }

        $config = self::TOUTES[$type];

        return new self(
            type: $type,
            cible: $config['cible'],
            jonction: $config['jonction'],
            cleEtrangere: $config['cleEtrangere'],
            libelle: $config['libelle'],
            champsAnnexes: $config['champsAnnexes'],
            couverture: $config['couverture'],
            controleur: $config['controleur'],
        );
    }

    /**
     * Liaisons proposées dans les listes de choix : type => libellé.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::TOUTES as $type => $config) {
            $options[$type] = ucfirst($config['libelle']);
        }

        return $options;
    }

    /**
     * La jonction porte-t-elle des colonnes éditables au-delà de l'ordre ?
     *
     * @return bool
     */
    public function estAnnotable(): bool
    {
        return $this->champsAnnexes !== [];
    }
}
