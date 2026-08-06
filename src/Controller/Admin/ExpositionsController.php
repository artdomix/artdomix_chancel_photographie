<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Expositions, passées et à venir, affichées sur `/expositions`.
 */
class ExpositionsController extends CrudController
{
    protected string $modele = 'Expositions';

    protected string $sectionTitre = 'Expositions';

    protected string $sectionSingulier = 'exposition';

    protected array $contain = ['Photos'];

    protected array $tri = ['Expositions.date_debut' => 'DESC'];

    protected array $colonnes = [
        'titre' => 'Titre',
        'lieu' => 'Lieu',
        'date_debut' => 'Début',
        'date_fin' => 'Fin',
        'actif' => 'En ligne',
    ];

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    protected function champs(EntityInterface $entite): array
    {
        return [
            'titre' => [],
            'description' => ['type' => 'textarea', 'rows' => 5],
            'lieu' => ['aide' => 'Nom de la galerie ou du lieu, et sa ville.'],
            'photo_id' => [
                'type' => 'select',
                'options' => $this->listePhotos(),
                'empty' => '— Aucune illustration —',
                'label' => 'Photo',
            ],
            'date_debut' => ['type' => 'date', 'empty' => true, 'label' => 'Date de début'],
            'date_fin' => [
                'type' => 'date',
                'empty' => true,
                'label' => 'Date de fin',
                'aide' => "Laisser vide pour un événement d'une seule journée.",
            ],
            'actif' => ['label' => 'En ligne'],
        ];
    }
}
