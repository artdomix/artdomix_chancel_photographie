<?php
declare(strict_types=1);

namespace App\Controller\Membre;

use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;

/**
 * Contrôleur de base de l'espace membre.
 *
 * Être connecté ne suffit pas : chaque action doit encore autoriser la ressource
 * qu'elle manipule via `GaleriePolicy` ou `MoodboardPolicy`. Un membre connecté
 * ne doit pas pouvoir consulter la galerie d'un autre client en changeant
 * l'identifiant dans l'URL — c'est précisément ce que les policies empêchent, et
 * c'est pourquoi `skipAuthorization()` n'est pas appelé ici.
 */
class AppController extends BaseController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Visiteur anonyme : redirection vers la connexion par le composant
        // d'authentification, plutôt qu'un 403 sans issue.
        if ($this->Authentication->getIdentity() === null) {
            $this->Authorization->skipAuthorization();

            return;
        }

        $this->viewBuilder()->setLayout('membre');
    }
}
