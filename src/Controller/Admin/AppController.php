<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use App\Model\Enum\Role;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Contrôleur de base du back-office.
 *
 * Toute la zone `/admin` exige le rôle `admin`. Le contrôle est fait ici, une
 * seule fois : un contrôleur d'administration nouvellement ajouté hérite de la
 * protection sans avoir à y penser.
 *
 * L'autorisation fine par entité n'est volontairement pas requise dans cette
 * zone — un administrateur a tous les droits sur le contenu. Écrire une policy
 * par table reviendrait à recopier vingt-cinq fois « retourne vrai ». Les
 * policies existent là où les droits diffèrent réellement : comptes, moodboards
 * et galeries côté membre.
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

        $identite = $this->Authentication->getIdentity();

        // Visiteur anonyme : on laisse le composant d'authentification faire son
        // travail, c'est-à-dire rediriger vers la connexion en mémorisant la page
        // visée. Répondre 403 ici afficherait une impasse au lieu d'un formulaire.
        if ($identite === null) {
            $this->Authorization->skipAuthorization();

            return;
        }

        // Connecté mais sans le rôle : là, c'est bien un refus.
        if ($identite->getOriginalData()->get('role') !== Role::Admin) {
            throw new ForbiddenException(__("Cette zone est réservée à l'administration."));
        }

        $this->Authorization->skipAuthorization();
        $this->viewBuilder()->setLayout('admin');

        // Ajouté ici plutôt que dans AppView : ce helper ne sert qu'aux listes du
        // back-office, le front n'a aucune raison de le charger.
        $this->viewBuilder()->addHelper('Admin');
    }
}
