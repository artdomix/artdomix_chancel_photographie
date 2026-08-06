<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Séances photo, qui relient un modèle, une date et un album.
 */
class ShootingsController extends CrudController
{
    protected string $modele = 'Shootings';

    protected string $sectionTitre = 'Shootings';

    protected string $sectionSingulier = 'shooting';

    protected array $contain = ['Modeles', 'Photos', 'Albums'];

    protected array $tri = ['Shootings.date_shooting' => 'DESC'];

    protected array $colonnes = [
        'titre' => 'Titre',
        'modele' => 'Modèle',
        'date_shooting' => 'Date',
        'lieu' => 'Lieu',
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
            'description' => ['type' => 'textarea', 'rows' => 4],
            'modele_id' => [
                'type' => 'select',
                'options' => $this->fetchTable('Modeles')->find('list')->toArray(),
                'empty' => '— Aucun modèle —',
                'label' => 'Modèle',
            ],
            'album_id' => [
                'type' => 'select',
                // `treeList` restitue la hiérarchie : sans elle, deux albums
                // homonymes dans des branches différentes seraient indiscernables.
                'options' => $this->fetchTable('Albums')->find('treeList', spacer: ' — ')->toArray(),
                'empty' => '— Aucun album —',
                'label' => 'Album associé',
            ],
            'photo_id' => [
                'type' => 'select',
                'options' => $this->listePhotos(),
                'empty' => '— Aucune vignette —',
                'label' => 'Vignette',
            ],
            'date_shooting' => ['type' => 'date', 'empty' => true, 'label' => 'Date de la séance'],
            'lieu' => [],
            'actif' => ['label' => 'En ligne'],
        ];
    }
}
