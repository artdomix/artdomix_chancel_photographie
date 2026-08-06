<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Catégories de vidéo (making-of, film court…).
 */
class TypevideosController extends CrudController
{
    protected string $modele = 'Typevideos';

    protected string $sectionTitre = 'Types de vidéo';

    protected string $sectionSingulier = 'type de vidéo';

    protected array $tri = ['Typevideos.nom' => 'ASC'];

    protected array $colonnes = ['nom' => 'Nom'];

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    protected function champs(EntityInterface $entite): array
    {
        return ['nom' => []];
    }
}
