<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\TypeConfig;
use Cake\Datasource\EntityInterface;

/**
 * Réglages du site, stockés en paires clé/valeur.
 *
 * Le stockage en lignes évite une migration à chaque nouveau réglage, mais rien
 * ne garde la clé unique côté formulaire : c'est la contrainte d'unicité de la
 * table qui refuse un doublon.
 */
class ConfigController extends CrudController
{
    protected string $modele = 'Config';

    protected string $sectionTitre = 'Réglages';

    protected string $sectionSingulier = 'réglage';

    protected array $tri = ['Config.cle' => 'ASC'];

    protected array $colonnes = [
        'cle' => 'Clé',
        'libelle' => 'Libellé',
        'valeur' => 'Valeur',
        'type' => 'Type',
    ];

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    protected function champs(EntityInterface $entite): array
    {
        return [
            'cle' => [
                'label' => 'Clé',
                // Renommer une clé déjà lue par le code reviendrait à supprimer
                // le réglage sans le dire : le champ se verrouille après création.
                'readonly' => !$entite->isNew(),
                'aide' => $entite->isNew()
                    ? 'Identifiant technique, en minuscules, sans espace.'
                    : 'La clé n\'est plus modifiable : du code s\'y réfère.',
            ],
            'libelle' => ['label' => 'Libellé', 'aide' => 'Ce que ce réglage signifie, en clair.'],
            'type' => ['type' => 'select', 'options' => TypeConfig::options()],
            'valeur' => ['type' => 'textarea', 'rows' => 4],
        ];
    }
}
