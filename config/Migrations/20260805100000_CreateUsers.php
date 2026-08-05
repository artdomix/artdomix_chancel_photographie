<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Comptes : administrateurs du back-office et membres (clients).
 *
 * L'ancien site avait une table `roles` séparée pour deux rôles seulement, et
 * comparait `role_id === '1'` — une identité stricte entre un entier et une chaîne
 * qui renvoyait toujours false. Une colonne ENUM supprime le problème à la racine.
 */
class CreateUsers extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Administrateurs et membres',
        ]);

        $table
            ->addColumn('email', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('role', 'enum', [
                'values' => ['admin', 'member'],
                'default' => 'member',
                'null' => false,
            ])
            ->addColumn('prenom', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('nom', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('societe', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('telephone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            // Jeton de réinitialisation de mot de passe, avec sa date d'expiration.
            ->addColumn('token', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('token_expire', 'datetime', ['null' => true])
            ->addColumn('derniere_connexion', 'datetime', ['null' => true])
            ->addTimestamps('created', 'modified')
            // 190 caractères : limite d'un index utf8mb4 sur les vieux MySQL (767 octets).
            ->addIndex(['email'], ['unique' => true, 'name' => 'idx_users_email'])
            ->addIndex(['token'], ['name' => 'idx_users_token'])
            ->addIndex(['role', 'actif'], ['name' => 'idx_users_role_actif'])
            ->create();
    }
}
