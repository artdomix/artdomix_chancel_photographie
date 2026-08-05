<?php
declare(strict_types=1);

namespace App\Controller\Admin;

/**
 * Tableau de bord de l'administration.
 */
class TableauController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $photos = $this->fetchTable('Photos');
        $messages = $this->fetchTable('Messages');

        $this->set([
            'nbPhotos' => $photos->find()->count(),
            'nbAlbums' => $this->fetchTable('Albums')->find()->count(),
            'nbMoodboards' => $this->fetchTable('Moodboards')->find()->count(),
            'nbMessagesNonLus' => $messages->find()->where(['lu' => false])->count(),
            'dernieresPhotos' => $photos->find('chronologique')->limit(6)->all(),
        ]);
        $this->set('title', 'Tableau de bord');
    }
}
