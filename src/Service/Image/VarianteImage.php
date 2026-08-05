<?php
declare(strict_types=1);

namespace App\Service\Image;

/**
 * Description d'une déclinaison d'image.
 *
 * L'ancien site encodait ces tailles dans des dossiers numérotés (`2/` = 2048 px,
 * `3/` = 500 px…) : il fallait connaître la convention par cœur pour relire le
 * code. Ici chaque variante porte son nom.
 */
final class VarianteImage
{
    /**
     * @param string $nom Nom de la variante, utilisé dans le nom de fichier.
     * @param int $largeurMax Largeur maximale en pixels.
     * @param int $qualite Qualité d'encodage (1-100).
     * @param bool $filigraneAutorise La variante peut-elle recevoir un filigrane ?
     */
    public function __construct(
        public readonly string $nom,
        public readonly int $largeurMax,
        public readonly int $qualite = 82,
        public readonly bool $filigraneAutorise = false,
    ) {
    }

    /**
     * Toutes les variantes produites à l'upload.
     *
     * Le filigrane n'est autorisé que sur les grands formats : sur une vignette
     * de 320 px il masquerait l'image sans protéger quoi que ce soit.
     *
     * @return array<string, self>
     */
    public static function toutes(): array
    {
        $variantes = [
            new self('thumb', 320, 78),
            new self('grid', 640, 80),
            new self('content', 1200, 82),
            new self('large', 2048, 84, true),
        ];

        $indexees = [];

        foreach ($variantes as $variante) {
            $indexees[$variante->nom] = $variante;
        }

        return $indexees;
    }
}
