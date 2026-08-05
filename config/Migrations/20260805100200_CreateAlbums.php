<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Arbre d'albums, en remplacement du couple `typecategories` / `categories`.
 *
 * La hiérarchie est gérée par le TreeBehavior du cœur CakePHP : `parent_id` porte
 * la relation, `lft` et `rght` l'encodent en jeu de nœuds imbriqués pour permettre
 * de récupérer une branche entière en une requête. Il n'y a délibérément pas de
 * colonne `child_id` : les enfants se déduisent, les stocker dans les deux sens
 * créerait deux sources de vérité à resynchroniser à chaque déplacement.
 */
class CreateAlbums extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $albums = $this->table('albums', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Arbre des albums du portfolio',
        ]);

        $albums
            ->addColumn('nom', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('parent_id', 'integer', ['null' => true])
            ->addColumn('lft', 'integer', ['null' => true])
            ->addColumn('rght', 'integer', ['null' => true])
            // Photo de couverture. Nullable et ON DELETE SET NULL : supprimer la
            // photo de couverture ne doit pas emporter l'album.
            ->addColumn('cover_photo_id', 'integer', ['null' => true])
            ->addColumn('visibilite', 'enum', [
                'values' => ['public', 'prive'],
                'default' => 'public',
                'null' => false,
            ])
            ->addColumn('ordre', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_albums_slug'])
            ->addIndex(['lft', 'rght'], ['name' => 'idx_albums_tree'])
            ->addIndex(['parent_id'], ['name' => 'idx_albums_parent'])
            ->addIndex(['actif', 'visibilite'], ['name' => 'idx_albums_visible'])
            ->create();

        // Auto-référence et couverture ajoutées après création : la table doit
        // exister avant de pouvoir se pointer elle-même.
        $this->table('albums')
            ->addForeignKey('parent_id', 'albums', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_albums_parent',
            ])
            ->addForeignKey('cover_photo_id', 'photos', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_albums_cover',
            ])
            ->update();

        $liaison = $this->table('albums_photos', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Liaison N-N albums / photos',
        ]);

        $liaison
            ->addColumn('album_id', 'integer', ['null' => false])
            ->addColumn('photo_id', 'integer', ['null' => false])
            // Tri manuel des photos album par album : la même photo peut occuper
            // une position différente dans deux albums.
            ->addColumn('ordre', 'integer', ['default' => 0, 'null' => false])
            ->addIndex(['album_id', 'photo_id'], [
                'unique' => true,
                'name' => 'idx_albums_photos_unique',
            ])
            ->addIndex(['album_id', 'ordre'], ['name' => 'idx_albums_photos_ordre'])
            ->addIndex(['photo_id'], ['name' => 'idx_albums_photos_photo'])
            ->addForeignKey('album_id', 'albums', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_albums_photos_album',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_albums_photos_photo',
            ])
            ->create();
    }
}
