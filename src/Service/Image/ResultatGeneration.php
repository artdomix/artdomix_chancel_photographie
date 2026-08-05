<?php
declare(strict_types=1);

namespace App\Service\Image;

/**
 * Ce que la génération des dérivés a réellement produit.
 *
 * Un objet de retour plutôt qu'un tableau associatif : les champs sont typés et
 * l'appelant ne peut pas se tromper de clé.
 */
final class ResultatGeneration
{
    /**
     * @param int $largeur Largeur de l'original.
     * @param int $hauteur Hauteur de l'original.
     * @param string $lqip Miniature encodée en data URI.
     * @param string $couleurDominante Couleur moyenne, en hexadécimal.
     * @param bool $avif Des fichiers AVIF ont-ils été écrits ?
     * @param bool $webp Des fichiers WebP ont-ils été écrits ?
     * @param list<string> $fichiers Chemins de tous les fichiers produits.
     */
    public function __construct(
        public readonly int $largeur,
        public readonly int $hauteur,
        public readonly string $lqip,
        public readonly string $couleurDominante,
        public readonly bool $avif,
        public readonly bool $webp,
        public readonly array $fichiers = [],
    ) {
    }

    /**
     * @return bool
     */
    public function estPortrait(): bool
    {
        return $this->hauteur > $this->largeur;
    }
}
