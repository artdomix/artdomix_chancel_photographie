<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Motifs proposés dans la liste déroulante du formulaire de contact.
 */
class DemandesController extends CrudController
{
    protected string $modele = 'Demandes';

    protected string $sectionTitre = 'Types de demande';

    protected string $sectionSingulier = 'type de demande';

    protected array $tri = ['Demandes.ordre' => 'ASC', 'Demandes.nom' => 'ASC'];

    protected array $colonnes = [
        'nom' => 'Nom',
        'ordre' => 'Ordre',
        'actif' => 'Proposé',
    ];

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    protected function champs(EntityInterface $entite): array
    {
        return [
            'nom' => [],
            'description' => ['type' => 'textarea', 'rows' => 3],
            'ordre' => [
                'type' => 'number',
                'label' => 'Ordre d\'affichage',
                'aide' => 'Le plus petit apparaît en premier.',
            ],
            'actif' => ['label' => 'Proposé dans le formulaire'],
        ];
    }
}
