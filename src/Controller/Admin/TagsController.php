<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\TypeTag;
use Cake\Datasource\EntityInterface;

/**
 * Mots-clés des photos.
 *
 * Jusqu'ici les tags ne pouvaient être créés que par l'action groupée de la
 * liste des photos, qui ne permet ni de les renommer ni de les typer.
 */
class TagsController extends CrudController
{
    protected string $modele = 'Tags';

    protected string $sectionTitre = 'Tags';

    protected string $sectionSingulier = 'tag';

    protected array $tri = ['Tags.type' => 'ASC', 'Tags.nom' => 'ASC'];

    protected array $colonnes = [
        'nom' => 'Nom',
        'type' => 'Famille',
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
            'type' => [
                'type' => 'select',
                'options' => TypeTag::options(),
                'label' => 'Famille',
                'aide' => 'Sert à regrouper les tags dans la recherche.',
            ],
            'description' => ['type' => 'textarea', 'rows' => 3],
        ];
    }
}
