<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Pages statiques : mentions légales, à propos, conditions de vente.
 */
class PagesController extends CrudController
{
    protected string $modele = 'Pages';

    protected string $sectionTitre = 'Pages';

    protected string $sectionSingulier = 'page';

    protected array $tri = ['Pages.titre' => 'ASC'];

    protected array $colonnes = [
        'titre' => 'Titre',
        'slug' => 'Adresse',
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
            'contenu' => [
                'type' => 'textarea',
                'rows' => 16,
                'aide' => 'HTML accepté.',
            ],
            'meta_description' => [
                'label' => 'Description pour les moteurs',
                'maxlength' => 255,
                'aide' => 'Environ 150 caractères, affichés sous le titre dans les résultats de recherche.',
            ],
            'actif' => ['label' => 'En ligne'],
        ];
    }
}
