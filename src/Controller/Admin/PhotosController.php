<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Association\AssociateurPhotos;
use App\Service\Association\Liaison;
use App\Service\Image\PhotoUploader;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Gestion des photos.
 *
 * Le contraste avec l'ancien `Admin/PhotosController::add()` est volontaire :
 * celui-ci faisait tout lui-même sur 90 lignes entrecoupées de `print_r()`. Ici
 * l'import est délégué à `PhotoUploader`, injecté par le conteneur, et l'action
 * se contente de traduire son résultat en réponse HTTP.
 */
class PhotosController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // L'uploader envoie un FormData construit en JavaScript : aucun jeton de
        // formulaire ne peut l'accompagner. Le CSRF, lui, reste exigé.
        $this->actionsJavascript(['ajouter']);
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $recherche = trim((string)$this->request->getQuery('q', ''));

        $requete = $this->fetchTable('Photos')->find()
            ->contain(['Exifs'])
            ->orderBy(['Photos.id' => 'DESC']);

        if ($recherche !== '') {
            $requete = $requete->find('recherche', terme: $recherche);
        }

        $this->set('photos', $this->paginate($requete, ['limit' => 60]));
        $this->set('recherche', $recherche);
        $this->set('destinations', $this->destinations());
        $this->set('title', 'Photos');

        if ($this->request->is('htmx')) {
            $this->render('/element/admin/liste_photos');
        }
    }

    /**
     * Import d'une photo.
     *
     * L'uploader du navigateur envoie **un fichier par requête** : sur un
     * hébergement mutualisé sans worker, c'est ce qui garde chaque requête
     * courte tout en permettant un import par lot côté interface.
     *
     * @param \App\Service\Image\PhotoUploader $uploader Service d'import.
     * @return \Cake\Http\Response|null
     */
    public function ajouter(PhotoUploader $uploader): ?Response
    {
        if (!$this->request->is('post')) {
            $this->set('title', 'Importer des photos');

            return null;
        }

        $fichier = $this->request->getUploadedFile('fichier');

        if ($fichier === null) {
            return $this->reponseImport(false, __('Aucun fichier reçu.'));
        }

        $resultat = $uploader->importer($fichier, [
            'titre' => $this->request->getData('titre'),
        ]);

        if (!$resultat->reussi) {
            return $this->reponseImport(false, $resultat->premiereErreur());
        }

        // Rattachement à un album si l'import vient d'une page d'album.
        $albumId = (int)$this->request->getData('album_id');

        if ($albumId > 0) {
            $this->fetchTable('AlbumsPhotos')->saveOrFail(
                $this->fetchTable('AlbumsPhotos')->newEntity([
                    'album_id' => $albumId,
                    'photo_id' => $resultat->photo->id,
                ]),
            );
        }

        return $this->reponseImport(true, (string)($resultat->photo->titre ?? $resultat->photo->fichier));
    }

    /**
     * @param string|null $id Identifiant de la photo.
     * @return \Cake\Http\Response|null
     */
    public function modifier(?string $id = null): ?Response
    {
        $photos = $this->fetchTable('Photos');
        $photo = $photos->find()
            ->where(['Photos.id' => (int)$id])
            ->contain(['Exifs', 'Tags', 'Albums'])
            ->first();

        if ($photo === null) {
            throw new NotFoundException();
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            $photos->patchEntity($photo, $this->request->getData(), [
                'associated' => ['Tags' => ['onlyIds' => true], 'Albums' => ['onlyIds' => true]],
            ]);

            if ($photos->save($photo)) {
                $this->Flash->success(__('Photo enregistrée.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__("La photo n'a pas pu être enregistrée."));
        }

        $this->set('photo', $photo);
        $this->set('tags', $photos->Tags->find('list', valueField: 'nom')->toArray());
        $this->set('albums', $photos->Albums->find('treeList', spacer: ' — ')->toArray());
        $this->set('title', 'Modifier une photo');

        return null;
    }

    /**
     * @param string|null $id Identifiant de la photo.
     * @param \App\Service\Image\PhotoUploader $uploader Service d'import.
     * @return \Cake\Http\Response|null
     */
    public function supprimer(?string $id, PhotoUploader $uploader): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $photo = $this->fetchTable('Photos')->find()->where(['id' => (int)$id])->first();

        if ($photo === null) {
            throw new NotFoundException();
        }

        if ($uploader->supprimer($photo)) {
            $this->Flash->success(__('Photo supprimée.'));
        } else {
            $this->Flash->error(__("La photo n'a pas pu être supprimée."));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Régénère les dérivés d'une photo depuis son original.
     *
     * Utile après un changement de réglage — activation du filigrane, ou
     * hébergeur qui gagne le support de l'AVIF.
     *
     * @param string|null $id Identifiant de la photo.
     * @param \App\Service\Image\PhotoUploader $uploader Service d'import.
     * @return \Cake\Http\Response|null
     */
    public function regenerer(?string $id, PhotoUploader $uploader): ?Response
    {
        $this->request->allowMethod('post');

        $photo = $this->fetchTable('Photos')->find()->where(['id' => (int)$id])->first();

        if ($photo === null) {
            throw new NotFoundException();
        }

        if ($uploader->regenerer($photo)) {
            $this->Flash->success(__('Dérivés régénérés.'));
        } else {
            $this->Flash->error(__("L'original est introuvable, les dérivés n'ont pas pu être régénérés."));
        }

        return $this->redirect($this->referer(['action' => 'index']));
    }

    /**
     * Édition en masse : taggage et activation de plusieurs photos à la fois.
     *
     * @return \Cake\Http\Response|null
     */
    public function enMasse(): ?Response
    {
        $this->request->allowMethod('post');

        $ids = array_filter(array_map('intval', (array)$this->request->getData('ids')));

        if ($ids === []) {
            $this->Flash->error(__('Aucune photo sélectionnée.'));

            return $this->redirect(['action' => 'index']);
        }

        $photos = $this->fetchTable('Photos');
        $action = (string)$this->request->getData('action_masse');

        match ($action) {
            'activer' => $photos->updateAll(['actif' => true], ['id IN' => $ids]),
            'desactiver' => $photos->updateAll(['actif' => false], ['id IN' => $ids]),
            'tagger' => $this->taggerEnMasse($ids, array_filter(array_map('intval', (array)$this->request->getData('tag_ids')))),
            'rattacher' => $this->rattacherEnMasse($ids),
            default => null,
        };

        // Le rattachement a déjà annoncé son résultat, qui n'est pas le nombre de
        // photos cochées : certaines pouvaient déjà être dans la cible.
        if ($action !== 'rattacher') {
            $this->Flash->success(__('{0} photos mises à jour.', count($ids)));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Destinations proposées à l'action groupée, groupées par famille.
     *
     * @return array<string, array<string, string>>
     */
    protected function destinations(): array
    {
        $groupes = [];

        // Les albums sont présentés en arbre : deux albums homonymes dans des
        // branches différentes seraient sinon indiscernables.
        $groupes['Albums'] = $this->prefixer(
            'album',
            $this->fetchTable('Albums')->find('treeList', spacer: ' — ')->toArray(),
        );
        $groupes['Moodboards'] = $this->prefixer(
            'moodboard',
            $this->fetchTable('Moodboards')->find('list', valueField: 'titre')
                ->orderBy(['Moodboards.created' => 'DESC'])->toArray(),
        );
        $groupes['Galeries client'] = $this->prefixer(
            'galerie',
            $this->fetchTable('Galeries')->find('list', valueField: 'nom')
                ->orderBy(['Galeries.created' => 'DESC'])->toArray(),
        );

        return array_filter($groupes);
    }

    /**
     * @param string $type Type de liaison.
     * @param array<int, string> $entites Identifiant => libellé.
     * @return array<string, string>
     */
    protected function prefixer(string $type, array $entites): array
    {
        $options = [];

        foreach ($entites as $id => $libelle) {
            $options[$type . ':' . $id] = $libelle;
        }

        return $options;
    }

    /**
     * Envoie un lot de photos vers un album, un moodboard ou une galerie.
     *
     * C'est le geste qui suit un import : deux cents photos viennent d'arriver,
     * elles vont toutes dans le même album. Passer par la fiche de chacune, ou
     * cliquer deux cents vignettes dans l'écran de sélection, serait absurde.
     *
     * @param list<int> $photoIds Photos visées.
     * @return void
     */
    protected function rattacherEnMasse(array $photoIds): void
    {
        $cible = (string)$this->request->getData('cible');

        // Le champ vaut « type:identifiant » : un seul menu déroulant réunit les
        // albums, les moodboards et les galeries, ce qui évite au photographe de
        // choisir d'abord une catégorie puis une cible.
        if (!str_contains($cible, ':')) {
            $this->Flash->error(__('Aucune destination choisie.'));

            return;
        }

        [$type, $cibleId] = explode(':', $cible, 2);

        $liaison = Liaison::pour($type);
        $associateur = new AssociateurPhotos();
        $entite = $associateur->cible($liaison, (int)$cibleId);

        $ajoutees = $associateur->attacherPlusieurs($liaison, $entite->id, $photoIds);
        $nom = $entite->get('nom') ?? $entite->get('titre');

        $this->Flash->success($ajoutees === 0
            ? __('Ces photos étaient déjà dans « {0} ».', $nom)
            : __('{0} photo(s) ajoutée(s) à « {1} ».', $ajoutees, $nom));
    }

    /**
     * @param list<int> $photoIds Photos visées.
     * @param list<int> $tagIds Tags à ajouter.
     * @return void
     */
    protected function taggerEnMasse(array $photoIds, array $tagIds): void
    {
        if ($tagIds === []) {
            return;
        }

        $liaison = $this->fetchTable('PhotosTags');

        // Les liaisons déjà présentes sont retirées de la liste : l'index unique
        // rejetterait un doublon et ferait échouer tout le lot.
        $existantes = $liaison->find()
            ->where(['photo_id IN' => $photoIds, 'tag_id IN' => $tagIds])
            ->all()
            ->map(fn($l) => $l->photo_id . '-' . $l->tag_id)
            ->toList();

        $nouvelles = [];

        foreach ($photoIds as $photoId) {
            foreach ($tagIds as $tagId) {
                if (!in_array($photoId . '-' . $tagId, $existantes, true)) {
                    $nouvelles[] = ['photo_id' => $photoId, 'tag_id' => $tagId];
                }
            }
        }

        if ($nouvelles !== []) {
            $liaison->saveManyOrFail($liaison->newEntities($nouvelles));
        }
    }

    /**
     * Réponse d'import : fragment htmx pour l'uploader, redirection sinon.
     *
     * @param bool $reussi L'import a-t-il abouti ?
     * @param string $message Message à afficher.
     * @return \Cake\Http\Response|null
     */
    protected function reponseImport(bool $reussi, string $message): ?Response
    {
        if ($this->request->is('htmx')) {
            $this->set(compact('reussi', 'message'));
            $this->viewBuilder()->disableAutoLayout();

            return $this->render('/element/admin/ligne_import');
        }

        $reussi ? $this->Flash->success($message) : $this->Flash->error($message);

        return $this->redirect(['action' => 'index']);
    }
}
