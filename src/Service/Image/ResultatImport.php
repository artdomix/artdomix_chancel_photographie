<?php
declare(strict_types=1);

namespace App\Service\Image;

use App\Model\Entity\Photo;

/**
 * Issue d'un import de photo : la photo créée, ou la liste des refus.
 *
 * Un objet plutôt qu'un booléen : l'appelant doit pouvoir afficher au
 * photographe *pourquoi* un fichier a été refusé, pas seulement qu'il l'a été.
 */
final class ResultatImport
{
    /**
     * @param bool $reussi L'import a-t-il abouti ?
     * @param \App\Model\Entity\Photo|null $photo Photo créée le cas échéant.
     * @param list<string> $erreurs Messages destinés à l'utilisateur.
     */
    private function __construct(
        public readonly bool $reussi,
        public readonly ?Photo $photo = null,
        public readonly array $erreurs = [],
    ) {
    }

    /**
     * @param \App\Model\Entity\Photo $photo Photo créée.
     * @return self
     */
    public static function succes(Photo $photo): self
    {
        return new self(true, $photo);
    }

    /**
     * @param list<string> $erreurs Messages d'erreur.
     * @return self
     */
    public static function echec(array $erreurs): self
    {
        return new self(false, null, $erreurs);
    }

    /**
     * @return string
     */
    public function premiereErreur(): string
    {
        return $this->erreurs[0] ?? __("L'import a échoué.");
    }
}
