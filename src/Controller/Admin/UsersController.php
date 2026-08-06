<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\Role;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;

/**
 * Comptes d'accès : administrateurs et membres.
 *
 * Sans cet écran, aucun compte client ne pouvait être créé depuis le site — or
 * une galerie de proofing doit être adressée à un membre. Il fallait passer par
 * `bin/cake console`, ce que le README documentait faute de mieux.
 *
 * Deux protections encadrent la section, parce qu'une erreur ici coupe l'accès
 * au site : on ne peut ni retirer son propre rôle d'administrateur, ni
 * désactiver ou supprimer son propre compte.
 */
class UsersController extends CrudController
{
    protected string $modele = 'Users';

    protected string $sectionTitre = 'Comptes';

    protected string $sectionSingulier = 'compte';

    protected array $tri = ['Users.role' => 'ASC', 'Users.email' => 'ASC'];

    protected array $colonnes = [
        'email' => 'Adresse',
        'nom' => 'Nom',
        'role' => 'Rôle',
        'derniere_connexion' => 'Dernière connexion',
        'actif' => 'Actif',
    ];

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité en cours d'édition.
     * @return array<string, array<string, mixed>>
     */
    protected function champs(EntityInterface $entite): array
    {
        return [
            'email' => ['label' => 'Adresse électronique'],
            'prenom' => ['label' => 'Prénom'],
            'nom' => [],
            'societe' => ['label' => 'Société'],
            'telephone' => ['label' => 'Téléphone'],
            'role' => [
                'type' => 'select',
                'options' => Role::options(),
                'label' => 'Rôle',
                // Se rétrograder soi-même fermerait l'administration à clé.
                'disabled' => $this->estSoiMeme($entite),
                'aide' => $this->estSoiMeme($entite)
                    ? 'Vous ne pouvez pas changer votre propre rôle.'
                    : 'Un administrateur accède à tout le back-office.',
            ],
            'password' => [
                'type' => 'password',
                'value' => '',
                'label' => 'Mot de passe',
                'required' => $entite->isNew(),
                'aide' => $entite->isNew()
                    ? '12 caractères minimum.'
                    : 'Laisser vide pour conserver le mot de passe actuel.',
            ],
            'actif' => [
                'label' => 'Compte actif',
                'disabled' => $this->estSoiMeme($entite),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $donnees Données brutes du formulaire.
     * @param \Cake\Datasource\EntityInterface $entite Entité visée.
     * @return array<string, mixed>
     */
    protected function preparerDonnees(array $donnees, EntityInterface $entite): array
    {
        // Champ vide sur une modification = « ne pas changer ». Sans ce retrait,
        // chaque enregistrement écraserait le mot de passe par une chaîne vide.
        if (!$entite->isNew() && ($donnees['password'] ?? '') === '') {
            unset($donnees['password']);
        }

        return $donnees;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entite Entité déjà patchée.
     * @param array<string, mixed> $donnees Données du formulaire.
     * @return void
     */
    protected function avantEnregistrement(EntityInterface $entite, array $donnees): void
    {
        // `role` est délibérément hors de l'assignation en masse : il est posé
        // ici, explicitement, après avoir écarté le cas du compte courant. Un
        // champ `role` glissé dans une requête ne peut donc rien promouvoir.
        if ($this->estSoiMeme($entite)) {
            $entite->set('actif', true);

            return;
        }

        $role = Role::tryFrom((string)($donnees['role'] ?? ''));

        if ($role !== null) {
            $entite->set('role', $role);
        } elseif ($entite->isNew()) {
            $entite->set('role', Role::Membre);
        }
    }

    /**
     * @param string|null $id Identifiant du compte.
     * @return \Cake\Http\Response|null
     */
    public function supprimer(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->estSoiMeme($this->parId((string)$id))) {
            $this->Flash->error(__('Vous ne pouvez pas supprimer votre propre compte.'));

            return $this->redirect(['action' => 'index']);
        }

        return parent::supprimer($id);
    }

    /**
     * @param string|null $id Identifiant du compte.
     * @return \Cake\Http\Response|null
     */
    public function basculer(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        if ($this->estSoiMeme($this->parId((string)$id))) {
            $this->Flash->error(__('Vous ne pouvez pas désactiver votre propre compte.'));

            return $this->redirect(['action' => 'index']);
        }

        return parent::basculer($id);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entite Compte visé.
     * @return bool
     */
    protected function estSoiMeme(EntityInterface $entite): bool
    {
        if ($entite->isNew()) {
            return false;
        }

        return (int)$entite->get('id') === (int)$this->Authentication->getIdentity()?->getIdentifier();
    }
}
