<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\AlbumsTable;
use App\Model\Table\PhotosTable;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Portfolio public : arbre d'albums, photo isolée, tags, recherche et carte.
 *
 * @property \App\Model\Table\AlbumsTable $Albums
 * @property \App\Model\Table\PhotosTable $Photos
 */
class PortfolioController extends AppController
{
    /**
     * @var \App\Model\Table\AlbumsTable
     */
    protected AlbumsTable $Albums;

    /**
     * Déclarée explicitement : depuis PHP 8.2, affecter une propriété non
     * déclarée émet une dépréciation.
     *
     * @var \App\Model\Table\PhotosTable
     */
    protected PhotosTable $Photos;

    /**
     * Nombre de photos par page dans les galeries.
     */
    protected const PAR_PAGE = 24;

    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Albums = $this->fetchTable('Albums');
        $this->Photos = $this->fetchTable('Photos');

        $this->autoriserPublic(['index', 'album', 'photo', 'tag', 'recherche', 'carte']);
    }

    /**
     * Racine du portfolio : les albums de premier niveau.
     *
     * @return void
     */
    public function index(): void
    {
        $albums = $this->Albums->find('publics')
            ->find('racines')
            ->contain(['CoverPhotos'])
            ->all();

        $this->set(compact('albums'));
        $this->set('title', 'Portfolio — Chancel Photographie');
    }

    /**
     * Un album et ses photos.
     *
     * Le chemin peut être imbriqué (`/portfolio/voyage/japon`) : seul le dernier
     * segment identifie l'album, les précédents servent à reconstituer le fil
     * d'Ariane et à donner une URL qui reflète l'arborescence.
     *
     * @param string ...$chemin Segments de l'URL.
     * @return void
     */
    public function album(string ...$chemin): void
    {
        $slug = end($chemin);

        if ($slug === false || $slug === '') {
            throw new NotFoundException();
        }

        $album = $this->Albums->find('publics')
            ->where(['Albums.slug' => $slug])
            ->contain(['CoverPhotos'])
            ->first();

        if ($album === null) {
            throw new NotFoundException(__('Album introuvable.'));
        }

        // Les sous-albums restent filtrés sur `publics` : un album privé imbriqué
        // dans un album public ne doit pas apparaître dans la navigation.
        $sousAlbums = $this->Albums->find('publics')
            ->where(['Albums.parent_id' => $album->id])
            ->contain(['CoverPhotos'])
            ->orderBy(['Albums.lft' => 'ASC'])
            ->all();

        $photos = $this->paginate(
            $this->Photos->find('actives')
                ->find('chronologique')
                ->matching('Albums', fn($q) => $q->where(['Albums.id' => $album->id])),
            ['limit' => self::PAR_PAGE],
        );

        // Fil d'Ariane reconstitué depuis l'arbre plutôt que depuis l'URL : une
        // URL bricolée à la main ne peut pas fabriquer un faux chemin.
        $ancetres = $this->Albums->find('path', for: $album->id)->all();

        $this->set(compact('album', 'sousAlbums', 'photos', 'ancetres'));
        $this->set('title', $album->nom . ' — Portfolio — Chancel Photographie');

        // Requête htmx de pagination : on ne renvoie que la grille.
        if ($this->request->is('htmx')) {
            $this->render('/element/grille_photos');
        }
    }

    /**
     * Page d'une photo.
     *
     * @param string|null $slug Slug de la photo.
     * @return void
     */
    public function photo(?string $slug = null): void
    {
        if ($slug === null) {
            throw new NotFoundException();
        }

        $photo = $this->Photos->find('actives')
            ->where(['Photos.slug' => $slug])
            ->contain(['Exifs', 'Tags', 'Albums'])
            ->first();

        if ($photo === null) {
            throw new NotFoundException(__('Photo introuvable.'));
        }

        $this->set(compact('photo'));
        $this->set('title', ($photo->titre ?? 'Photo') . ' — Chancel Photographie');
    }

    /**
     * Photos portant un tag.
     *
     * @param string|null $slug Slug du tag.
     * @return void
     */
    public function tag(?string $slug = null): void
    {
        if ($slug === null) {
            throw new NotFoundException();
        }

        $tag = $this->fetchTable('Tags')->find()->where(['slug' => $slug])->first();

        if ($tag === null) {
            throw new NotFoundException(__('Tag introuvable.'));
        }

        $photos = $this->paginate(
            $this->Photos->find('actives')
                ->find('chronologique')
                ->matching('Tags', fn($q) => $q->where(['Tags.id' => $tag->id])),
            ['limit' => self::PAR_PAGE],
        );

        $this->set(compact('tag', 'photos'));
        $this->set('title', $tag->nom . ' — Chancel Photographie');

        if ($this->request->is('htmx')) {
            $this->render('/element/grille_photos');
        }
    }

    /**
     * Recherche plein texte.
     *
     * @return void
     */
    public function recherche(): void
    {
        $terme = trim((string)$this->request->getQuery('q', ''));
        $photos = [];

        if ($terme !== '') {
            $photos = $this->paginate(
                $this->Photos->find('actives')
                    ->find('recherche', terme: $terme)
                    ->contain(['Exifs']),
                ['limit' => self::PAR_PAGE],
            );
        }

        $tags = $this->fetchTable('Tags')->find()->orderBy(['type' => 'ASC', 'nom' => 'ASC'])->all();

        $this->set(compact('terme', 'photos', 'tags'));
        $this->set('title', 'Recherche — Chancel Photographie');

        // La recherche au fil de la frappe ne rafraîchit que les résultats.
        if ($this->request->is('htmx')) {
            $this->render('/element/resultats_recherche');
        }
    }

    /**
     * Carte des photos géolocalisées.
     *
     * @return void
     */
    public function carte(): void
    {
        $photos = $this->Photos->find('actives')
            ->find('geolocalisees')
            ->all();

        // Les points sont sérialisés côté serveur : la carte reçoit du JSON prêt
        // à l'emploi plutôt qu'une requête supplémentaire au chargement.
        $points = [];

        foreach ($photos as $photo) {
            $points[] = [
                'lat' => (float)$photo->exif->gps_lat,
                'lng' => (float)$photo->exif->gps_lng,
                'titre' => (string)($photo->titre ?? ''),
                'url' => $this->request->getAttribute('webroot') . 'photo/' . $photo->slug,
                'vignette' => $this->request->getAttribute('webroot')
                    . 'media/photos/' . $photo->fichier . '-thumb.jpeg',
            ];
        }

        $this->set(compact('points'));
        $this->set('nbPoints', count($points));
        $this->set('title', 'Carte — Chancel Photographie');
    }
}
