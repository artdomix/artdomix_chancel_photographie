<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Modèles photographiés, rattachés aux shootings.
 */
class ModelesController extends CrudController
{
    protected string $modele = 'Modeles';

    protected string $sectionTitre = 'Modèles';

    protected string $sectionSingulier = 'modèle';

    protected array $contain = ['Photos'];

    protected array $tri = ['Modeles.nom' => 'ASC'];

    protected array $colonnes = [
        'nom' => 'Nom',
        'instagram' => 'Instagram',
        'actif' => 'En ligne',
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
            'photo_id' => [
                'type' => 'select',
                'options' => $this->listePhotos(),
                'empty' => '— Aucun portrait —',
                'label' => 'Portrait',
            ],
            'instagram' => ['aide' => 'Nom du compte, sans l\'arobase.'],
            'site' => ['label' => 'Site web'],
            'actif' => ['label' => 'En ligne'],
        ];
    }
}
