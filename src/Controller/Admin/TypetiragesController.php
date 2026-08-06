<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Familles de tirage (baryté, pigmentaire…), proposées au formulaire des tirages.
 */
class TypetiragesController extends CrudController
{
    protected string $modele = 'Typetirages';

    protected string $sectionTitre = 'Types de tirage';

    protected string $sectionSingulier = 'type de tirage';

    protected array $tri = ['Typetirages.nom' => 'ASC'];

    protected array $colonnes = [
        'nom' => 'Nom',
        'description' => 'Description',
    ];

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    protected function champs(EntityInterface $entite): array
    {
        return [
            'nom' => [],
            'description' => ['type' => 'textarea', 'rows' => 4],
        ];
    }
}
