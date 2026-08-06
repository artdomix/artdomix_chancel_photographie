<?php
declare(strict_types=1);

namespace App\Service\Galerie;

/**
 * Issue d'une bascule de favori.
 *
 * Un simple booléen ne suffirait pas : « la photo n'est pas retenue » recouvre
 * deux situations très différentes — elle vient d'être retirée, ou le quota
 * empêchait de l'ajouter. L'appelant doit pouvoir le dire au client.
 */
final class ResultatSelection
{
    /**
     * @param bool $retenue La photo est-elle retenue à l'issue de l'opération ?
     * @param bool $quotaAtteint L'ajout a-t-il été refusé faute de place ?
     */
    public function __construct(
        public readonly bool $retenue,
        public readonly bool $quotaAtteint = false,
    ) {
    }
}
