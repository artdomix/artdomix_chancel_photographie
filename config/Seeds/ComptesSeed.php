<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

/**
 * Comptes réels d'accès au site : un administrateur et un membre.
 *
 * Séparé de `DemoSeed` pour pouvoir être rejoué seul, sans réinjecter les
 * photos et moodboards de démonstration.
 *
 * Idempotent : les comptes existants portant ces adresses sont supprimés avant
 * réinsertion, ce qui permet de relancer le seed pour réinitialiser un mot de
 * passe oublié en développement.
 *
 * ⚠️ Le mot de passe est en clair dans ce fichier versionné. Il est destiné à
 * la première connexion et doit être changé depuis l'espace du compte. Il fait
 * par ailleurs 8 caractères, alors que `UsersTable::validationMotDePasse()` en
 * exige 12 : le seed écrit en SQL direct et ne passe donc pas par la validation,
 * mais un changement via le formulaire imposera un mot de passe plus long.
 */
class ComptesSeed extends BaseSeed
{
    /**
     * Mot de passe initial commun aux deux comptes.
     */
    protected const MOT_DE_PASSE = 'artdomix';

    /**
     * @return void
     */
    public function run(): void
    {
        $maintenant = date('Y-m-d H:i:s');
        $hasher = new DefaultPasswordHasher();

        $comptes = [
            [
                'email' => 'dodo15@msn.com',
                'role' => 'admin',
                'prenom' => 'Dominique',
                'nom' => 'Chancel',
                'societe' => null,
            ],
            [
                'email' => 'celine.benner@gmail.com',
                'role' => 'member',
                'prenom' => 'Céline',
                'nom' => 'Benner',
                'societe' => null,
            ],
        ];

        // Supprimé plutôt que mis à jour : la table `users` est référencée par
        // des clés étrangères en ON DELETE SET NULL ou CASCADE, l'intégrité est
        // donc préservée, et repartir d'une ligne neuve évite de traîner un
        // jeton de réinitialisation périmé.
        //
        // Passe par le constructeur de requêtes plutôt que par une chaîne SQL :
        // les valeurs sont liées, pas concaténées.
        $this->getAdapter()->getConnection()
            ->deleteQuery('users')
            ->where(['email IN' => array_column($comptes, 'email')])
            ->execute();

        $lignes = [];

        foreach ($comptes as $compte) {
            $lignes[] = $compte + [
                'password' => $hasher->hash(self::MOT_DE_PASSE),
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ];
        }

        $this->table('users')->insert($lignes)->save();
    }
}
