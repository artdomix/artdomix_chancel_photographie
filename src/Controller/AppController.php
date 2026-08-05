<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * Contrôleur de base.
 *
 * La règle du projet tient en une phrase : **tout est refusé par défaut**.
 *
 * L'ancien site faisait l'inverse — `$this->Auth->allow()` ouvrait l'ensemble
 * des actions, puis `deny()` refermait uniquement si le préfixe valait `admin`.
 * Oublier ce préfixe sur une nouvelle zone la rendait publique sans que rien ne
 * le signale. Ici, une action non déclarée publique est inaccessible : l'oubli
 * produit une erreur visible plutôt qu'une fuite silencieuse.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class AppController extends Controller
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');

        // Protection contre la falsification de formulaire : l'ancien site n'en
        // avait aucune sur son back-office.
        $this->loadComponent('FormProtection', [
            'unlockedActions' => [],
        ]);
    }

    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Les réponses htmx sont des fragments : elles ne doivent pas embarquer
        // le layout complet, sinon chaque échange réinjecterait l'en-tête et le
        // pied de page au milieu de la page.
        if ($this->request->is('htmx')) {
            $this->viewBuilder()->disableAutoLayout();
        }
    }

    /**
     * Dispense certaines actions du jeton anti-falsification de formulaire.
     *
     * `FormProtection` valide un jeton `_Token` calculé sur les champs du
     * formulaire rendu. C'est excellent pour un formulaire classique, mais
     * inapplicable à une requête émise en JavaScript (upload par glisser-déposer,
     * réordonnancement, bascule d'un favori) qui ne provient d'aucun formulaire.
     *
     * **La protection CSRF reste entière** : ces requêtes doivent présenter
     * l'en-tête `X-CSRF-Token`, vérifié par `CsrfProtectionMiddleware`. Ce qui
     * est levé ici, c'est uniquement le contrôle d'intégrité des champs.
     *
     * @param list<string> $actions Actions pilotées en JavaScript.
     * @return void
     */
    protected function actionsJavascript(array $actions): void
    {
        $this->FormProtection->setConfig('unlockedActions', $actions);
    }

    /**
     * Déclare les actions accessibles sans être connecté.
     *
     * À appeler dans le `beforeFilter()` des contrôleurs publics. Passer par
     * cette méthode plutôt que d'appeler directement le composant rend le fait
     * explicite et cherchable : `grep -r autoriserPublic src/` donne la liste
     * complète des points d'entrée ouverts.
     *
     * @param list<string> $actions Actions à ouvrir au public.
     * @return void
     */
    protected function autoriserPublic(array $actions): void
    {
        $this->Authentication->allowUnauthenticated($actions);

        // L'autorisation n'est court-circuitée que si l'action EN COURS fait
        // partie de la liste. Appeler `skipAuthorization()` sans cette condition
        // désarmerait le contrôle pour toutes les autres actions du contrôleur —
        // exactement le genre d'ouverture trop large qui a piégé l'ancien site.
        if (in_array($this->request->getParam('action'), $actions, true)) {
            $this->Authorization->skipAuthorization();
        }
    }
}
