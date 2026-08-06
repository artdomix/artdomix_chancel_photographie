<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\Role;
use Cake\Datasource\EntityInterface;

/**
 * Articles du blog.
 *
 * Un article n'est visible sur `/blog` que s'il est actif **et** que sa date de
 * publication est passée : les deux champs se complètent, ils ne font pas double
 * emploi. Le premier retire l'article, le second le programme.
 */
class ArticlesController extends CrudController
{
    protected string $modele = 'Articles';

    protected string $sectionTitre = 'Articles';

    protected string $sectionSingulier = 'article';

    protected array $contain = ['Photos', 'Auteurs'];

    protected array $tri = ['Articles.publie_le' => 'DESC'];

    protected array $colonnes = [
        'titre' => 'Titre',
        'publie_le' => 'Publié le',
        'vues' => 'Vues',
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
            'chapeau' => [
                'type' => 'textarea',
                'rows' => 3,
                'aide' => 'Résumé affiché dans la liste du blog et dans les aperçus de partage.',
            ],
            'contenu' => ['type' => 'textarea', 'rows' => 18, 'aide' => 'HTML accepté.'],
            'photo_id' => [
                'type' => 'select',
                'options' => $this->listePhotos(),
                'empty' => '— Aucune illustration —',
                'label' => 'Illustration',
            ],
            'auteur_id' => [
                'type' => 'select',
                'options' => $this->fetchTable('Users')->find('list', valueField: 'email')
                    ->where(['role' => Role::Admin])->toArray(),
                'empty' => '— Vous —',
                'label' => 'Auteur',
            ],
            'publie_le' => [
                'type' => 'datetime-local',
                'empty' => true,
                'label' => 'Publié le',
                'aide' => 'Une date future programme la parution. Vide = brouillon.',
            ],
            'actif' => ['label' => 'En ligne'],
        ];
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité déjà patchée.
     * @param array<string, mixed> $donnees Données du formulaire.
     * @return void
     */
    protected function avantEnregistrement(EntityInterface $entite, array $donnees): void
    {
        // Sans auteur choisi, c'est celui qui écrit. Signer l'article de personne
        // laisserait une page publique sans attribution.
        if ($entite->get('auteur_id') === null) {
            $entite->set('auteur_id', $this->Authentication->getIdentity()?->getIdentifier());
        }
    }
}
