<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\View\Exception\MissingTemplateException;

/**
 * Accueil et pages éditoriales.
 */
class PagesController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->autoriserPublic(['accueil', 'display']);
    }

    /**
     * Page d'accueil.
     *
     * @return void
     */
    public function accueil(): void
    {
        $photos = $this->fetchTable('Photos');

        $this->set([
            // Photo d'ouverture : la plus récente prise de vue, pas le dernier
            // import — c'est la date de capture qui raconte le travail en cours.
            'heros' => $photos->find('actives')->find('chronologique')->first(),
            'selection' => $photos->find('actives')->find('chronologique')->limit(8)->all(),
            'albums' => $this->fetchTable('Albums')->find('publics')->find('racines')
                ->contain(['CoverPhotos'])->limit(3)->all(),
            'articles' => $this->fetchTable('Articles')->find()
                ->where([
                    'Articles.actif' => true,
                    'Articles.publie_le IS NOT' => null,
                    'Articles.publie_le <=' => new DateTime(),
                ])
                ->orderBy(['Articles.publie_le' => 'DESC'])
                ->contain(['Photos'])
                ->limit(2)
                ->all(),
        ]);

        $this->set('title', 'Chancel Photographie — Portrait, voyage, automobile');
    }

    /**
     * Pages statiques éventuelles (mentions légales…).
     *
     * @param string ...$path Segments du chemin.
     * @return \Cake\Http\Response|null
     */
    public function display(string ...$path): ?Response
    {
        if ($path === []) {
            return $this->redirect('/');
        }

        try {
            return $this->render(implode('/', $path));
        } catch (MissingTemplateException $e) {
            if (Configure::read('debug')) {
                throw $e;
            }

            throw new NotFoundException();
        }
    }
}
