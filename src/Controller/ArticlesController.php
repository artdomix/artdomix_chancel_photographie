<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\ArticlesTable;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;

/**
 * Blog public.
 *
 * @property \App\Model\Table\ArticlesTable $Articles
 */
class ArticlesController extends AppController
{
    /**
     * Déclarée explicitement : depuis PHP 8.2, affecter une propriété non
     * déclarée émet une dépréciation.
     *
     * @var \App\Model\Table\ArticlesTable
     */
    protected ArticlesTable $Articles;

    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Articles = $this->fetchTable('Articles');
        $this->autoriserPublic(['index', 'voir', 'commenter']);
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $articles = $this->paginate(
            $this->publies()->contain(['Photos']),
            ['limit' => 10],
        );

        $this->set(compact('articles'));
        $this->set('title', 'Blog — Chancel Photographie');
    }

    /**
     * @param string|null $slug Slug de l'article.
     * @return void
     */
    public function voir(?string $slug = null): void
    {
        if ($slug === null) {
            throw new NotFoundException();
        }

        $article = $this->publies()
            ->where(['Articles.slug' => $slug])
            ->contain([
                'Photos',
                // Seuls les commentaires validés sont exposés : la modération
                // est a priori, un commentaire n'apparaît pas avant relecture.
                'Commentaires' => fn($q) => $q->where(['Commentaires.valide' => true])
                    ->orderBy(['Commentaires.created' => 'ASC']),
            ])
            ->first();

        if ($article === null) {
            throw new NotFoundException(__('Article introuvable.'));
        }

        // Compteur de vues incrémenté en SQL direct : un `save()` toucherait
        // `modified` et ferait remonter l'article comme s'il venait d'être édité.
        $this->Articles->updateAll(
            ['vues = vues + 1'],
            ['id' => $article->id],
        );

        $commentaire = $this->Articles->Commentaires->newEmptyEntity();

        $this->set(compact('article', 'commentaire'));
        $this->set('title', $article->titre . ' — Chancel Photographie');
    }

    /**
     * Dépôt d'un commentaire.
     *
     * @param string|null $slug Slug de l'article.
     * @return \Cake\Http\Response|null
     */
    public function commenter(?string $slug = null): ?Response
    {
        $this->request->allowMethod('post');

        $article = $this->publies()->where(['Articles.slug' => $slug])->first();

        if ($article === null) {
            throw new NotFoundException();
        }

        $commentaires = $this->Articles->Commentaires;
        $commentaire = $commentaires->newEntity($this->request->getData());
        $commentaire->article_id = $article->id;
        $commentaire->ip = $this->request->clientIp();
        // Jamais publié directement : c'est la porte d'entrée du spam.
        $commentaire->valide = false;

        if ($commentaires->save($commentaire)) {
            $this->Flash->success(__('Merci, votre commentaire sera publié après relecture.'));
        } else {
            $this->Flash->error(__("Le commentaire n'a pas pu être enregistré."));
        }

        return $this->redirect(['action' => 'voir', $slug]);
    }

    /**
     * Articles réellement visibles : actifs et dont la date de publication est
     * passée. Un article programmé ne doit pas fuiter avant l'heure.
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function publies(): SelectQuery
    {
        return $this->Articles->find()
            ->where([
                'Articles.actif' => true,
                'Articles.publie_le IS NOT' => null,
                'Articles.publie_le <=' => new DateTime(),
            ])
            ->orderBy(['Articles.publie_le' => 'DESC']);
    }
}
