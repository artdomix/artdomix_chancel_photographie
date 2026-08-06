<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;

/**
 * Modération des commentaires du blog.
 *
 * La modération est **a priori** : un commentaire déposé sur `/blog` n'apparaît
 * pas tant qu'il n'a pas été validé ici. C'est pour cela que la liste trie les
 * plus récents en premier — ce sont ceux qui attendent.
 */
class CommentairesController extends CrudController
{
    protected string $modele = 'Commentaires';

    protected string $sectionTitre = 'Commentaires';

    protected string $sectionSingulier = 'commentaire';

    protected array $contain = ['Articles'];

    protected array $tri = ['Commentaires.created' => 'DESC'];

    /**
     * C'est `valide`, et non `actif`, qui décide de la publication.
     */
    protected string $colonneBascule = 'valide';

    protected array $libellesBascule = ['Masquer', 'Valider'];

    /**
     * Les commentaires viennent du site public : en créer un depuis
     * l'administration reviendrait à fabriquer un faux avis.
     */
    protected bool $creationPossible = false;

    protected array $colonnes = [
        'created' => 'Reçu le',
        'auteur' => 'Auteur',
        'article' => 'Article',
        'contenu' => 'Extrait',
        'valide' => 'Publié',
    ];

    /**
     * L'article de rattachement n'est pas modifiable : déplacer un commentaire
     * d'un article à un autre lui ferait dire autre chose que ce que son auteur
     * a écrit.
     *
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    protected function champs(EntityInterface $entite): array
    {
        return [
            'auteur' => ['label' => 'Nom affiché'],
            'email' => ['aide' => 'Jamais publié, sert seulement à répondre.'],
            'contenu' => ['type' => 'textarea', 'rows' => 8],
            'valide' => ['label' => 'Publié sur le site'],
        ];
    }
}
