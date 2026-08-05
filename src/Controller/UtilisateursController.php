<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use App\Model\Enum\Role;
use App\Model\Table\UsersTable;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Cake\Utility\Security;

/**
 * Connexion, déconnexion et réinitialisation de mot de passe.
 *
 * Le contrôleur vit hors préfixe : la même page de connexion sert aux
 * administrateurs et aux membres, la redirection après connexion dépend ensuite
 * du rôle.
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UtilisateursController extends AppController
{
    /**
     * Déclarée explicitement : depuis PHP 8.2, affecter une propriété non
     * déclarée émet une dépréciation.
     *
     * @var \App\Model\Table\UsersTable
     */
    protected UsersTable $Users;

    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Users = $this->fetchTable('Users');
        $this->autoriserPublic(['connexion', 'motDePasseOublie', 'reinitialiser']);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function connexion(): ?Response
    {
        $resultat = $this->Authentication->getResult();

        if ($resultat !== null && $resultat->isValid()) {
            $utilisateur = $this->Authentication->getIdentity()->getOriginalData();

            $this->Users->updateAll(
                ['derniere_connexion' => new DateTime()],
                ['id' => $utilisateur->id],
            );

            // `redirect` est fourni par le middleware quand l'utilisateur visait
            // une page protégée. getLoginRedirect() valide que la cible est
            // interne : un `?redirect=https://ailleurs` serait sinon un tremplin
            // pour du hameçonnage.
            $cible = $this->Authentication->getLoginRedirect() ?? $this->accueilSelonRole($utilisateur->role);

            return $this->redirect($cible);
        }

        if ($this->request->is('post')) {
            $this->Flash->error(__('Identifiants incorrects.'));
        }

        $this->viewBuilder()->setLayout('connexion');

        return null;
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function deconnexion(): ?Response
    {
        $this->Authorization->skipAuthorization();
        $this->Authentication->logout();
        $this->Flash->success(__('Vous êtes déconnecté.'));

        return $this->redirect(['action' => 'connexion']);
    }

    /**
     * Demande de réinitialisation.
     *
     * @return \Cake\Http\Response|null
     */
    public function motDePasseOublie(): ?Response
    {
        if ($this->request->is('post')) {
            $email = (string)$this->request->getData('email');
            $utilisateur = $this->Users->find('actifs')->where(['email' => $email])->first();

            if ($utilisateur !== null) {
                $utilisateur->token = bin2hex(Security::randomBytes(32));
                $utilisateur->token_expire = (new DateTime())->addHours(2);
                $this->Users->save($utilisateur);

                $this->envoyerLienReinitialisation($utilisateur);
            }

            // Le message est identique que l'adresse existe ou non : répondre
            // différemment permettrait d'énumérer les comptes du site.
            $this->Flash->success(__('Si un compte existe pour cette adresse, un lien vient de vous être envoyé.'));

            return $this->redirect(['action' => 'connexion']);
        }

        $this->viewBuilder()->setLayout('connexion');

        return null;
    }

    /**
     * Saisie du nouveau mot de passe.
     *
     * @param string|null $token Jeton reçu par e-mail.
     * @return \Cake\Http\Response|null
     */
    public function reinitialiser(?string $token = null): ?Response
    {
        $utilisateur = $token === null ? null : $this->Users->find()
            ->where([
                'token' => $token,
                'token_expire >' => new DateTime(),
                'actif' => true,
            ])
            ->first();

        if ($utilisateur === null) {
            $this->Flash->error(__('Ce lien est invalide ou a expiré.'));

            return $this->redirect(['action' => 'motDePasseOublie']);
        }

        if ($this->request->is(['post', 'put'])) {
            $this->Users->patchEntity($utilisateur, [
                'password' => $this->request->getData('password'),
            ], ['validate' => 'motDePasse']);

            // Le jeton est brûlé dès qu'il a servi : un lien de réinitialisation
            // ne doit fonctionner qu'une fois.
            $utilisateur->token = null;
            $utilisateur->token_expire = null;

            if ($this->Users->save($utilisateur)) {
                $this->Flash->success(__('Mot de passe mis à jour, vous pouvez vous connecter.'));

                return $this->redirect(['action' => 'connexion']);
            }

            $this->Flash->error(__('Mot de passe refusé, merci de réessayer.'));
        }

        $this->set(compact('utilisateur', 'token'));
        $this->viewBuilder()->setLayout('connexion');

        return null;
    }

    /**
     * @param \App\Model\Enum\Role $role Rôle de l'utilisateur connecté.
     * @return array<string, mixed>
     */
    protected function accueilSelonRole(Role $role): array
    {
        return $role === Role::Admin
            ? ['prefix' => 'Admin', 'controller' => 'Tableau', 'action' => 'index']
            : ['prefix' => 'Membre', 'controller' => 'Tableau', 'action' => 'index'];
    }

    /**
     * @param \App\Model\Entity\User $utilisateur Destinataire.
     * @return void
     */
    protected function envoyerLienReinitialisation(User $utilisateur): void
    {
        $lien = Router::url([
            'prefix' => false,
            'controller' => 'Utilisateurs',
            'action' => 'reinitialiser',
            $utilisateur->token,
        ], true);

        (new Mailer('default'))
            ->setTo($utilisateur->email)
            ->setSubject(__('Réinitialisation de votre mot de passe'))
            ->deliver(__("Bonjour,\n\nPour choisir un nouveau mot de passe : {0}\n\nCe lien expire dans deux heures.", $lien));
    }
}
