<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Album;
use App\Model\Entity\Photo;
use App\Model\Table\AlbumsTable;
use App\Model\Table\PhotosTable;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query\SelectQuery;

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
            $this->photosDeLAlbum($album->id),
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

        // Une photo peut appartenir à plusieurs séries : celle d'où l'on vient
        // est passée en paramètre, sinon on retient la première publique. Sans ce
        // contexte, la page serait une impasse — or c'est elle que les moteurs
        // indexent et que l'on partage.
        $serie = $this->serieDeReference($photo, (string)$this->request->getQuery('serie', ''));
        $voisines = $serie === null ? [] : $this->voisinesDansLaSerie($serie, $photo->id);

        $this->set(compact('photo', 'serie'));
        $this->set('precedente', $voisines['precedente'] ?? null);
        $this->set('suivante', $voisines['suivante'] ?? null);
        $this->set('memeSerie', $voisines['autres'] ?? []);
        $this->set('ancetres', $serie === null
            ? []
            : $this->Albums->find('path', for: $serie->id)->all());
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

    /**
     * Photos d'un album, dans l'ordre voulu par le photographe.
     *
     * `albums_photos.ordre` prime, la chronologie départage les ex æquo. C'est ce
     * qui permet à une série d'être *racontée* plutôt que déroulée par date — et
     * un album jamais ordonné garde exactement le classement d'avant, puisque
     * toutes ses lignes partagent le même `ordre`.
     *
     * @param int $albumId Identifiant de l'album.
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function photosDeLAlbum(int $albumId): SelectQuery
    {
        return $this->Photos->find('actives')
            ->contain(['Exifs'])
            ->leftJoinWith('Exifs')
            ->matching('Albums', fn($q) => $q->where(['Albums.id' => $albumId]))
            ->orderBy([
                'AlbumsPhotos.ordre' => 'ASC',
                'COALESCE(Exifs.date_capture, Photos.created)' => 'DESC',
                'Photos.id' => 'DESC',
            ]);
    }

    /**
     * Série servant de contexte à une photo.
     *
     * @param \App\Model\Entity\Photo $photo Photo affichée.
     * @param string $demandee Slug de série passé en paramètre, éventuellement vide.
     * @return \App\Model\Entity\Album|null
     */
    protected function serieDeReference(Photo $photo, string $demandee): ?Album
    {
        $publics = $this->Albums->find('publics')
            ->matching('Photos', fn($q) => $q->where(['Photos.id' => $photo->id]));

        if ($demandee !== '') {
            // Le paramètre vient de l'URL : il est confronté aux albums publics
            // de cette photo, jamais suivi tel quel.
            $serie = (clone $publics)->where(['Albums.slug' => $demandee])->first();

            if ($serie !== null) {
                return $serie;
            }
        }

        return $publics->orderBy(['Albums.lft' => 'ASC'])->first();
    }

    /**
     * Photo précédente, suivante, et quelques autres de la même série.
     *
     * @param \App\Model\Entity\Album $serie Série de référence.
     * @param int $photoId Photo affichée.
     * @return array<string, mixed>
     */
    protected function voisinesDansLaSerie(Album $serie, int $photoId): array
    {
        // La série entière est chargée : il faut connaître la position de la
        // photo dans la séquence pour désigner ses voisines, et une série de
        // photographe se compte en dizaines, pas en milliers.
        $sequence = $this->photosDeLAlbum($serie->id)->all()->toList();
        $position = null;

        foreach ($sequence as $index => $candidate) {
            if ($candidate->id === $photoId) {
                $position = $index;

                break;
            }
        }

        if ($position === null) {
            return [];
        }

        $autres = array_values(array_filter(
            $sequence,
            fn($candidate) => $candidate->id !== $photoId,
        ));

        return [
            'precedente' => $sequence[$position - 1] ?? null,
            'suivante' => $sequence[$position + 1] ?? null,
            'autres' => array_slice($autres, 0, 6),
        ];
    }
}
