<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\Table;

/**
 * Socle des écrans d'administration à quatre opérations.
 *
 * Une quinzaine de tables du site — livres, expositions, tags, pages, types de
 * tirage… — se gèrent exactement de la même façon : une liste, un formulaire,
 * une suppression. Écrire quinze contrôleurs identiques reviendrait à créer
 * quinze endroits où corriger le même oubli ; les sous-classes ne déclarent donc
 * que ce qui les distingue vraiment (le modèle, les colonnes de la liste, les
 * champs du formulaire).
 *
 * Ce qui sort de ce moule garde son contrôleur propre : les photos ont un
 * pipeline d'images, les moodboards des jetons de partage, les commentaires une
 * modération. Le socle sert les cas réguliers, pas les cas particuliers.
 */
abstract class CrudController extends AppController
{
    /**
     * Nom du modèle, par exemple « Livres ».
     *
     * @var string
     */
    protected string $modele = '';

    /**
     * Libellé de la section, affiché en titre de page.
     *
     * @var string
     */
    protected string $sectionTitre = '';

    /**
     * Libellé au singulier, employé dans les messages et le bouton de création.
     *
     * @var string
     */
    protected string $sectionSingulier = '';

    /**
     * Associations chargées pour la liste et le formulaire.
     *
     * @var array<string>
     */
    protected array $contain = [];

    /**
     * Tri par défaut de la liste.
     *
     * @var array<string, string>
     */
    protected array $tri = [];

    /**
     * Colonne booléenne pilotée par le bouton de bascule de la liste.
     *
     * Vaut `actif` presque partout, mais `valide` pour les commentaires : c'est
     * la même opération — publier ou retirer — sous un autre nom.
     *
     * @var string
     */
    protected string $colonneBascule = 'actif';

    /**
     * Libellés du bouton de bascule : [action quand c'est publié, action sinon].
     *
     * @var array{0: string, 1: string}
     */
    protected array $libellesBascule = ['Retirer', 'Mettre en ligne'];

    /**
     * La section accepte-t-elle la création d'un enregistrement ?
     *
     * Faux pour les commentaires : ils arrivent du site public, les créer depuis
     * l'administration reviendrait à en fabriquer de faux.
     *
     * @var bool
     */
    protected bool $creationPossible = true;

    /**
     * Colonnes du tableau : chemin de la valeur => libellé de l'en-tête.
     *
     * Le chemin accepte la notation pointée (`photo.titre`) pour atteindre une
     * association.
     *
     * @var array<string, string>
     */
    protected array $colonnes = [];

    /**
     * Champs du formulaire : nom => options passées au FormHelper.
     *
     * Le tableau est construit par méthode plutôt que par propriété parce que la
     * plupart des formulaires ont besoin de listes déroulantes issues de la base,
     * qu'on ne peut pas écrire dans une propriété.
     *
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    abstract protected function champs(EntityInterface $entite): array;

    /**
     * Liste paginée.
     *
     * @return void
     */
    public function index(): void
    {
        $requete = $this->table()->find()->contain($this->contain);

        if ($this->tri !== []) {
            $requete->orderBy($this->tri);
        }

        $this->set('entites', $this->paginate($requete, ['limit' => 40]));
        $this->set('colonnes', $this->colonnes);
        $this->set('colonneBascule', $this->table()->getSchema()->hasColumn($this->colonneBascule)
            ? $this->colonneBascule
            : null);
        $this->set('libellesBascule', $this->libellesBascule);
        $this->set('creationPossible', $this->creationPossible);
        $this->set('sectionTitre', $this->sectionTitre);
        $this->set('sectionSingulier', $this->sectionSingulier);
        $this->set('title', $this->sectionTitre);

        $this->render('/Admin/Crud/index');
    }

    /**
     * Création ou édition selon la présence d'un identifiant.
     *
     * @param string|null $id Identifiant de l'enregistrement, ou null pour une création.
     * @return \Cake\Http\Response|null
     */
    public function modifier(?string $id = null): ?Response
    {
        $table = $this->table();

        if ($id === null && !$this->creationPossible) {
            throw new NotFoundException();
        }

        $entite = $id === null ? $table->newEmptyEntity() : $this->parId($id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $donnees = $this->preparerDonnees($this->request->getData(), $entite);
            $table->patchEntity($entite, $donnees);
            $this->avantEnregistrement($entite, $donnees);

            if ($table->save($entite)) {
                $this->Flash->success(__('{0} enregistré.', $this->sectionSingulier));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__("L'enregistrement a échoué. Vérifiez les champs signalés."));
        }

        $this->set('entite', $entite);
        $this->set('champs', $this->typerChamps($this->champs($entite)));
        $this->set('sectionTitre', $this->sectionTitre);
        $this->set('sectionSingulier', $this->sectionSingulier);
        $this->set('title', $entite->isNew()
            ? __('Nouveau : {0}', $this->sectionSingulier)
            : __('Modifier : {0}', $this->sectionSingulier));

        return $this->render('/Admin/Crud/modifier');
    }

    /**
     * @param string|null $id Identifiant de l'enregistrement.
     * @return \Cake\Http\Response|null
     */
    public function supprimer(?string $id = null): ?Response
    {
        // POST obligatoire : une suppression atteignable en GET serait déclenchée
        // par n'importe quel préchargeur de lien.
        $this->request->allowMethod(['post', 'delete']);

        $table = $this->table();
        $entite = $this->parId((string)$id);

        if ($table->delete($entite)) {
            $this->Flash->success(__('{0} supprimé.', $this->sectionSingulier));
        } else {
            // Une contrainte de clé étrangère en RESTRICT peut refuser la
            // suppression : le dire plutôt que d'afficher un succès trompeur.
            $this->Flash->error(__('Suppression impossible : cet élément est encore référencé.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bascule la colonne `actif` — mettre en ligne ou retirer sans ouvrir le
     * formulaire, ce qui est l'opération la plus courante sur une liste.
     *
     * @param string|null $id Identifiant de l'enregistrement.
     * @return \Cake\Http\Response|null
     */
    public function basculer(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        $table = $this->table();

        if (!$table->getSchema()->hasColumn($this->colonneBascule)) {
            throw new NotFoundException();
        }

        $entite = $this->parId((string)$id);
        $entite->set($this->colonneBascule, !$entite->get($this->colonneBascule));

        if ($table->save($entite)) {
            $this->Flash->success($entite->get($this->colonneBascule)
                ? __('Mis en ligne.')
                : __('Retiré du site.'));
        } else {
            $this->Flash->error(__("L'état n'a pas pu être changé."));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Point d'extension : transformer les données du formulaire avant le patch.
     *
     * Sert aux champs qui ne se recopient pas tels quels — un mot de passe laissé
     * vide qu'il faut retirer, par exemple.
     *
     * @param array<string, mixed> $donnees Données brutes du formulaire.
     * @param \Cake\Datasource\EntityInterface $entite Entité visée.
     * @return array<string, mixed>
     */
    protected function preparerDonnees(array $donnees, EntityInterface $entite): array
    {
        return $donnees;
    }

    /**
     * Marque explicitement les champs booléens.
     *
     * Le FormHelper sait déduire le type d'une colonne, mais le gabarit doit le
     * connaître avant lui : une case à cocher ne prend pas le même habillage
     * qu'un champ texte. La question est tranchée ici, où le schéma est à portée
     * de main, plutôt que dans la vue.
     *
     * @param array<string, array<string, mixed>> $champs Champs déclarés.
     * @return array<string, array<string, mixed>>
     */
    protected function typerChamps(array $champs): array
    {
        $schema = $this->table()->getSchema();

        foreach ($champs as $nom => $options) {
            if (!isset($options['type']) && $schema->getColumnType($nom) === 'boolean') {
                $champs[$nom]['type'] = 'checkbox';
            }
        }

        return $champs;
    }

    /**
     * Point d'extension : ajuster l'entité entre le patch et l'enregistrement.
     *
     * Nécessaire pour les champs volontairement exclus de l'assignation en masse
     * — le rôle d'un compte, notamment, qu'un formulaire ne doit jamais pouvoir
     * changer par la simple présence d'un champ homonyme.
     *
     * @param \Cake\Datasource\EntityInterface $entite Entité déjà patchée.
     * @param array<string, mixed> $donnees Données du formulaire.
     * @return void
     */
    protected function avantEnregistrement(EntityInterface $entite, array $donnees): void
    {
    }

    /**
     * @return \Cake\ORM\Table
     */
    protected function table(): Table
    {
        return $this->fetchTable($this->modele);
    }

    /**
     * @param string $id Identifiant recherché.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function parId(string $id): EntityInterface
    {
        $table = $this->table();

        $entite = $table->find()
            ->where([$table->aliasField('id') => (int)$id])
            ->contain($this->contain)
            ->first();

        if ($entite === null) {
            throw new NotFoundException();
        }

        return $entite;
    }

    /**
     * Liste déroulante des photos, réutilisée par la plupart des formulaires :
     * livres, expositions, vidéos et modèles portent tous une photo d'illustration.
     *
     * @return array<int, string>
     */
    protected function listePhotos(): array
    {
        return $this->fetchTable('Photos')->find('list', valueField: 'titre')
            ->orderBy(['Photos.created' => 'DESC'])
            ->limit(300)
            ->toArray();
    }
}
