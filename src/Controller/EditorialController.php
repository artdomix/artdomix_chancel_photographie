<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * Pages éditoriales : livres, tirages, expositions, vidéos.
 *
 * Ces quatre rubriques existaient sur l'ancien site et sont conservées telles
 * quelles côté métier. Elles sont regroupées dans un seul contrôleur parce
 * qu'elles font toutes la même chose — lister ce qui est actif, puis afficher
 * une fiche — et que quatre contrôleurs de trente lignes identiques auraient été
 * quatre endroits où corriger le même oubli.
 */
class EditorialController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->autoriserPublic([
            'livres', 'livre',
            'tirages',
            'expositions', 'exposition',
            'videos', 'video',
        ]);
    }

    /**
     * @return void
     */
    public function livres(): void
    {
        $livres = $this->actifs($this->fetchTable('Livres'))
            ->contain(['Photos'])
            // Les parutions sans date passent en fin de liste plutôt que d'être
            // écartées : un livre à paraître doit rester visible.
            ->orderBy(['Livres.date_parution' => 'DESC', 'Livres.titre' => 'ASC'])
            ->all();

        $this->set(compact('livres'));
        $this->set('title', 'Livres — Chancel Photographie');
    }

    /**
     * @param string|null $slug Slug du livre.
     * @return void
     */
    public function livre(?string $slug = null): void
    {
        $livre = $this->parSlug($this->fetchTable('Livres'), $slug, ['Photos']);

        $this->set(compact('livre'));
        $this->set('title', $livre->titre . ' — Chancel Photographie');
    }

    /**
     * Les tirages n'ont pas de fiche propre : ce sont des déclinaisons d'une
     * photo, et c'est la photo qui porte la page.
     *
     * @return void
     */
    public function tirages(): void
    {
        $tirages = $this->actifs($this->fetchTable('Tirages'))
            ->contain(['Photos', 'Typetirages'])
            ->orderBy(['Tirages.prix' => 'ASC'])
            ->all();

        $this->set(compact('tirages'));
        $this->set('title', 'Tirages — Chancel Photographie');
    }

    /**
     * @return void
     */
    public function expositions(): void
    {
        $expositions = $this->actifs($this->fetchTable('Expositions'))
            ->contain(['Photos'])
            ->orderBy(['Expositions.date_debut' => 'DESC'])
            ->all();

        // Séparées à l'affichage : une exposition passée et une exposition à
        // venir n'appellent pas la même lecture, et les mélanger obligerait le
        // visiteur à comparer les dates lui-même.
        $aujourdhui = date('Y-m-d');
        $courantes = [];
        $passees = [];

        foreach ($expositions as $exposition) {
            $fin = $exposition->date_fin?->format('Y-m-d');

            if ($fin === null || $fin >= $aujourdhui) {
                $courantes[] = $exposition;
            } else {
                $passees[] = $exposition;
            }
        }

        $this->set(compact('courantes', 'passees'));
        $this->set('title', 'Expositions — Chancel Photographie');
    }

    /**
     * @param string|null $slug Slug de l'exposition.
     * @return void
     */
    public function exposition(?string $slug = null): void
    {
        $exposition = $this->parSlug($this->fetchTable('Expositions'), $slug, ['Photos']);

        $this->set(compact('exposition'));
        $this->set('title', $exposition->titre . ' — Chancel Photographie');
    }

    /**
     * @return void
     */
    public function videos(): void
    {
        $videos = $this->actifs($this->fetchTable('Videos'))
            ->contain(['Photos', 'Typevideos'])
            ->orderBy(['Videos.created' => 'DESC'])
            ->all();

        $this->set(compact('videos'));
        $this->set('title', 'Vidéos — Chancel Photographie');
    }

    /**
     * @param string|null $slug Slug de la vidéo.
     * @return void
     */
    public function video(?string $slug = null): void
    {
        $video = $this->parSlug($this->fetchTable('Videos'), $slug, ['Photos', 'Typevideos']);

        $this->set(compact('video'));
        $this->set('title', $video->titre . ' — Chancel Photographie');
    }

    /**
     * @param \Cake\ORM\Table $table Table interrogée.
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function actifs(Table $table): SelectQuery
    {
        // `aliasField` plutôt que « actif » nu : la plupart de ces requêtes
        // contiennent des Photos, qui ont aussi une colonne `actif`, et MySQL
        // refuserait la condition comme ambiguë.
        return $table->find()->where([$table->aliasField('actif') => true]);
    }

    /**
     * @param \Cake\ORM\Table $table Table interrogée.
     * @param string|null $slug Slug recherché.
     * @param array<string> $contain Associations à charger.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function parSlug(Table $table, ?string $slug, array $contain = []): object
    {
        if ($slug === null) {
            throw new NotFoundException();
        }

        $entite = $this->actifs($table)
            ->where([$table->aliasField('slug') => $slug])
            ->contain($contain)
            ->first();

        if ($entite === null) {
            throw new NotFoundException();
        }

        return $entite;
    }
}
