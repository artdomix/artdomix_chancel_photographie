<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Galeries client (proofing) : livraison d'un shooting à un client.
 *
 * Proche des moodboards dans la forme, distincte dans l'usage : ici le client
 * sélectionne ses favoris, commente et télécharge. Les deux notions restent des
 * tables séparées — les fusionner derrière une colonne `type` obligerait chaque
 * requête à filtrer et mélangerait deux cycles de vie sans rapport.
 */
class CreateGaleries extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $galeries = $this->table('galeries', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Galeries client livrées',
        ]);

        $galeries
            ->addColumn('nom', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('client_id', 'integer', ['null' => true])
            ->addColumn('share_token', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('expires_at', 'datetime', ['null' => true])
            ->addColumn('telechargement_actif', 'boolean', ['default' => false, 'null' => false])
            // Nombre de favoris que le client peut retenir (0 = illimité) : c'est le
            // forfait vendu, pas une contrainte technique.
            ->addColumn('quota_favoris', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('date_livraison', 'date', ['null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['share_token'], ['unique' => true, 'name' => 'idx_galeries_token'])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_galeries_slug'])
            ->addIndex(['client_id'], ['name' => 'idx_galeries_client'])
            ->addForeignKey('client_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_galeries_client',
            ])
            ->create();

        $liaison = $this->table('galeries_photos', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Photos composant une galerie client',
        ]);

        $liaison
            ->addColumn('galerie_id', 'integer', ['null' => false])
            ->addColumn('photo_id', 'integer', ['null' => false])
            ->addColumn('ordre', 'integer', ['default' => 0, 'null' => false])
            ->addIndex(['galerie_id', 'photo_id'], [
                'unique' => true,
                'name' => 'idx_galeries_photos_unique',
            ])
            ->addIndex(['galerie_id', 'ordre'], ['name' => 'idx_galeries_photos_ordre'])
            ->addForeignKey('galerie_id', 'galeries', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_galeries_photos_galerie',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_galeries_photos_photo',
            ])
            ->create();

        $favoris = $this->table('galerie_favoris', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Photos retenues par le client',
        ]);

        $favoris
            ->addColumn('galerie_id', 'integer', ['null' => false])
            ->addColumn('photo_id', 'integer', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => true])
            // Identifie un visiteur venu par le lien de partage, sans compte.
            ->addColumn('session_key', 'string', ['limit' => 64, 'null' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['galerie_id', 'photo_id', 'user_id'], [
                'unique' => true,
                'name' => 'idx_galerie_favoris_unique',
            ])
            ->addIndex(['galerie_id', 'session_key'], ['name' => 'idx_galerie_favoris_session'])
            ->addForeignKey('galerie_id', 'galeries', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_galerie_favoris_galerie',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_galerie_favoris_photo',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_galerie_favoris_user',
            ])
            ->create();

        $commentaires = $this->table('galerie_commentaires', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Retours du client sur une photo livrée',
        ]);

        $commentaires
            ->addColumn('galerie_id', 'integer', ['null' => false])
            ->addColumn('photo_id', 'integer', ['null' => true])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('auteur', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('contenu', 'text', ['null' => false])
            ->addColumn('lu', 'boolean', ['default' => false, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['galerie_id', 'created'], ['name' => 'idx_gal_commentaires_galerie'])
            ->addForeignKey('galerie_id', 'galeries', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_gal_commentaires_galerie',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_gal_commentaires_photo',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_gal_commentaires_user',
            ])
            ->create();
    }
}
