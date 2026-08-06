<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Tirages proposés à la vente, affichés sur `/tirages`.
 *
 * Un tirage est une déclinaison d'une photo — format, papier, prix — et n'a donc
 * pas de titre propre : c'est la photo qui le nomme dans la liste.
 */
class TiragesController extends CrudController
{
    protected string $modele = 'Tirages';

    protected string $sectionTitre = 'Tirages';

    protected string $sectionSingulier = 'tirage';

    protected array $contain = ['Photos', 'Typetirages'];

    protected array $tri = ['Tirages.prix' => 'ASC'];

    protected array $colonnes = [
        'photo' => 'Photo',
        'typetirage' => 'Type',
        'format' => 'Format',
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
            // Seul champ obligatoire sans valeur par défaut : la liste n'a donc
            // pas d'option vide, un tirage sans photo n'aurait aucun sens.
            'photo_id' => ['type' => 'select', 'options' => $this->listePhotos(), 'label' => 'Photo'],
            'typetirage_id' => [
                'type' => 'select',
                'options' => $this->fetchTable('Typetirages')->find('list')->toArray(),
                'empty' => '— Aucun type —',
                'label' => 'Type de tirage',
            ],
            'format' => ['aide' => 'Par exemple « 30 × 40 cm ».'],
            'papier' => [],
            'prix' => ['type' => 'number', 'step' => '0.01', 'min' => 0, 'label' => 'Prix en euros'],
            'tirage_limite' => [
                'type' => 'number',
                'min' => 0,
                'label' => 'Édition limitée à',
                'aide' => "Nombre d'exemplaires. Laisser vide pour une édition ouverte.",
            ],
            'actif' => ['label' => 'En ligne'],
        ];
    }
}
