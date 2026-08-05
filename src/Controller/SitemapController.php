<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;

/**
 * Plan de site et flux RSS.
 *
 * Générés à la demande plutôt que stockés dans un fichier : le volume reste
 * modeste (quelques centaines d'URL) et un fichier statique finit toujours par
 * être périmé.
 */
class SitemapController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->autoriserPublic(['index', 'robots', 'rss']);
    }

    /**
     * Plan de site XML.
     *
     * Ne référence que ce qui est réellement public : les albums privés, les
     * moodboards et les galeries client en sont exclus par construction, puisque
     * seules les requêtes filtrées les alimentent.
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $urls = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => '/portfolio', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/carte', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/blog', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => '/tirages', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/livres', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/expositions', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/videos', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/contact', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ];

        // Fiches éditoriales. Le préfixe d'URL est la seule chose qui change
        // d'une rubrique à l'autre, d'où la boucle plutôt que trois blocs.
        foreach (['livres' => 'Livres', 'expositions' => 'Expositions', 'videos' => 'Videos'] as $prefixe => $table) {
            $entites = $this->fetchTable($table)->find()
                ->where([$this->fetchTable($table)->aliasField('actif') => true])
                ->all();

            foreach ($entites as $entite) {
                $urls[] = [
                    'loc' => '/' . $prefixe . '/' . $entite->slug,
                    'lastmod' => $entite->modified?->format('Y-m-d'),
                    'priority' => '0.5',
                    'changefreq' => 'monthly',
                ];
            }
        }

        foreach ($this->fetchTable('Albums')->find('publics')->all() as $album) {
            $urls[] = [
                'loc' => '/portfolio/' . $album->slug,
                'lastmod' => $album->modified?->format('Y-m-d'),
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach ($this->fetchTable('Photos')->find('actives')->all() as $photo) {
            $urls[] = [
                'loc' => '/photo/' . $photo->slug,
                'lastmod' => $photo->modified?->format('Y-m-d'),
                'priority' => '0.6',
                'changefreq' => 'yearly',
            ];
        }

        foreach ($this->fetchTable('Tags')->find()->all() as $tag) {
            $urls[] = ['loc' => '/tag/' . $tag->slug, 'priority' => '0.4', 'changefreq' => 'monthly'];
        }

        foreach ($this->articlesPublies() as $article) {
            $urls[] = [
                'loc' => '/blog/' . $article->slug,
                'lastmod' => $article->modified?->format('Y-m-d'),
                'priority' => '0.6',
                'changefreq' => 'monthly',
            ];
        }

        $this->set(compact('urls'));
        $this->viewBuilder()->disableAutoLayout();

        return $this->render()->withType('application/xml');
    }

    /**
     * `robots.txt` généré, pour que l'URL du plan de site suive le domaine.
     *
     * @return \Cake\Http\Response
     */
    public function robots(): Response
    {
        $lignes = [
            'User-agent: *',
            // Les zones privées et les liens de partage n'ont rien à faire dans
            // un index. Les pages de partage portent aussi un meta noindex :
            // robots.txt empêche l'exploration, le meta empêche l'indexation
            // d'une URL découverte autrement.
            'Disallow: /admin',
            'Disallow: /membre',
            'Disallow: /m/',
            'Disallow: /g/',
            'Disallow: /connexion',
            'Disallow: /reinitialiser',
            '',
            // La spécification exige une URL absolue : un chemin relatif est
            // ignoré par les moteurs.
            'Sitemap: ' . Router::url('/sitemap.xml', true),
        ];

        return $this->response
            ->withType('text/plain')
            ->withStringBody(implode("\n", $lignes) . "\n");
    }

    /**
     * Flux RSS du blog.
     *
     * @return \Cake\Http\Response
     */
    public function rss(): Response
    {
        $this->set('articles', $this->articlesPublies()->limit(20));
        $this->viewBuilder()->disableAutoLayout();

        return $this->render()->withType('application/rss+xml');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function articlesPublies(): SelectQuery
    {
        return $this->fetchTable('Articles')->find()
            ->where([
                'Articles.actif' => true,
                'Articles.publie_le IS NOT' => null,
                'Articles.publie_le <=' => new DateTime(),
            ])
            ->orderBy(['Articles.publie_le' => 'DESC']);
    }
}
