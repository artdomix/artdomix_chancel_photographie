<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\Plateforme;
use Cake\Datasource\EntityInterface;

/**
 * Vidéos intégrées, affichées sur `/videos`.
 */
class VideosController extends CrudController
{
    protected string $modele = 'Videos';

    protected string $sectionTitre = 'Vidéos';

    protected string $sectionSingulier = 'vidéo';

    protected array $contain = ['Photos', 'Typevideos'];

    protected array $tri = ['Videos.created' => 'DESC'];

    protected array $colonnes = [
        'titre' => 'Titre',
        'typevideo' => 'Type',
        'plateforme' => 'Plateforme',
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
            'typevideo_id' => [
                'type' => 'select',
                'options' => $this->fetchTable('Typevideos')->find('list')->toArray(),
                'empty' => '— Aucun type —',
                'label' => 'Type',
            ],
            'photo_id' => [
                'type' => 'select',
                'options' => $this->listePhotos(),
                'empty' => '— Aucune vignette —',
                'label' => 'Vignette',
            ],
            'plateforme' => ['type' => 'select', 'options' => Plateforme::options()],
            'video_ref' => [
                'label' => 'Identifiant de la vidéo',
                // On stocke l'identifiant, pas l'URL : c'est l'application qui
                // compose l'iframe, ce qui évite qu'une URL collée à la main
                // atterrisse telle quelle dans un attribut `src`.
                'aide' => 'Seulement l\'identifiant : « aqz-KE-bpKQ » pour YouTube, « 76979871 » pour Vimeo.',
            ],
            'duree' => ['type' => 'number', 'min' => 0, 'label' => 'Durée en secondes'],
            'actif' => ['label' => 'En ligne'],
        ];
    }
}
