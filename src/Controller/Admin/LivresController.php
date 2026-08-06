<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Ouvrages publiés, affichés sur `/livres`.
 */
class LivresController extends CrudController
{
    protected string $modele = 'Livres';

    protected string $sectionTitre = 'Livres';

    protected string $sectionSingulier = 'livre';

    protected array $contain = ['Photos'];

    protected array $tri = ['Livres.date_parution' => 'DESC'];

    protected array $colonnes = [
        'titre' => 'Titre',
        'date_parution' => 'Parution',
        'prix' => 'Prix',
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
            'photo_id' => [
                'type' => 'select',
                'options' => $this->listePhotos(),
                'empty' => '— Aucune couverture —',
                'label' => 'Photo de couverture',
            ],
            'date_parution' => [
                'type' => 'date',
                'empty' => true,
                'label' => 'Date de parution',
                'aide' => 'Laisser vide pour un ouvrage à paraître.',
            ],
            'prix' => ['type' => 'number', 'step' => '0.01', 'min' => 0, 'label' => 'Prix en euros'],
            'lien_achat' => ['label' => "Lien d'achat", 'aide' => 'URL complète, boutique externe.'],
            'actif' => ['label' => 'En ligne'],
        ];
    }
}
