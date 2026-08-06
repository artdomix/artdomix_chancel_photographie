<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Gestion de l'arbre des albums et de l'ordre des photos.
 */
class AlbumsController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Le réordonnancement envoie du JSON depuis le glisser-déposer.
        $this->actionsJavascript(['ordonner']);
    }

    /**
     * @return void
     */
    public function index(): void
    {
        // `threaded` restitue la hiérarchie complète en une requête, grâce aux
        // bornes lft/rght du TreeBehavior.
        $albums = $this->fetchTable('Albums')->find('threaded')
            ->orderBy(['Albums.lft' => 'ASC'])
            ->all();

        $this->set(compact('albums'));
        $this->set('title', 'Albums');
    }

    /**
     * Création ou édition selon la présence d'un identifiant.
     *
     * @param string|null $id Identifiant de l'album, ou null pour une création.
     * @return \Cake\Http\Response|null
     */
    public function modifier(?string $id = null): ?Response
    {
        $albums = $this->fetchTable('Albums');

        // Un album neuf n'a ni photos ni bornes d'arbre : le TreeBehavior les
        // pose à l'enregistrement, d'après le `parent_id` choisi.
        $album = $id === null
            ? $albums->newEmptyEntity()
            : $albums->find()
                ->where(['Albums.id' => (int)$id])
                ->contain(['Photos' => fn($q) => $q->orderBy(['AlbumsPhotos.ordre' => 'ASC'])])
                ->first();

        if ($album === null) {
            throw new NotFoundException();
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            $albums->patchEntity($album, $this->request->getData());

            if ($albums->save($album)) {
                $this->Flash->success(__('Album enregistré.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__("L'album n'a pas pu être enregistré."));
        }

        $parents = $albums->find('treeList', spacer: ' — ');

        // L'album lui-même est retiré de la liste des parents possibles : se
        // choisir soi-même comme parent corromprait l'arbre. À la création il
        // n'a pas encore d'identifiant, la restriction ne s'applique pas.
        if (!$album->isNew()) {
            $parents->where(['Albums.id !=' => $album->id]);
        }

        $this->set('album', $album);
        $this->set('parents', $parents->toArray());
        $this->set('title', $album->isNew() ? 'Nouvel album' : 'Modifier un album');

        return null;
    }

    /**
     * Supprime un album.
     *
     * Le `TreeBehavior` réordonne les bornes des voisins, et la clé étrangère de
     * `parent_id` est en `ON DELETE CASCADE` : supprimer une branche emporte ses
     * sous-albums. La confirmation du gabarit le dit explicitement, parce que la
     * liste ne montre pas d'un coup d'œil combien d'enfants sont concernés.
     *
     * Les photos, elles, survivent : elles ne sont liées que par la table de
     * jonction `albums_photos`.
     *
     * @param string|null $id Identifiant de l'album.
     * @return \Cake\Http\Response|null
     */
    public function supprimer(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $albums = $this->fetchTable('Albums');
        $album = $albums->find()->where(['Albums.id' => (int)$id])->first();

        if ($album === null) {
            throw new NotFoundException();
        }

        if ($albums->delete($album)) {
            $this->Flash->success(__('Album supprimé.'));
        } else {
            $this->Flash->error(__("L'album n'a pas pu être supprimé."));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Enregistre le nouvel ordre des photos d'un album.
     *
     * Reçoit du JSON depuis le glisser-déposer et répond en JSON : l'appelant
     * n'a besoin que de savoir si l'enregistrement a réussi.
     *
     * @param string|null $id Identifiant de l'album.
     * @return \Cake\Http\Response
     */
    public function ordonner(?string $id = null): Response
    {
        $this->request->allowMethod('post');

        $album = $this->fetchTable('Albums')->find()->where(['id' => (int)$id])->first();

        if ($album === null) {
            throw new NotFoundException();
        }

        $ordre = (array)($this->request->getData('ordre') ?? []);
        $liaison = $this->fetchTable('AlbumsPhotos');

        foreach ($ordre as $position => $photoId) {
            $liaison->updateAll(
                ['ordre' => (int)$position],
                ['album_id' => $album->id, 'photo_id' => (int)$photoId],
            );
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['ok' => true, 'nb' => count($ordre)]));
    }

    /**
     * Déplace un album d'un cran dans la fratrie.
     *
     * Passe par les méthodes du TreeBehavior : écrire `lft`/`rght` à la main
     * corromprait le jeu de nœuds imbriqués.
     *
     * @param string|null $id Identifiant de l'album.
     * @param string|null $sens `haut` ou `bas`.
     * @return \Cake\Http\Response|null
     */
    public function deplacer(?string $id = null, ?string $sens = null): ?Response
    {
        $this->request->allowMethod('post');

        $albums = $this->fetchTable('Albums');
        $album = $albums->find()->where(['id' => (int)$id])->first();

        if ($album === null) {
            throw new NotFoundException();
        }

        $sens === 'haut' ? $albums->moveUp($album) : $albums->moveDown($album);

        return $this->redirect(['action' => 'index']);
    }
}
