<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Contenus éditoriaux repris de l'ancien site : blog, pages, livres, expositions,
 * tirages, vidéos, modèles et shootings.
 *
 * Les noms français d'origine sont conservés — c'est le vocabulaire du métier et
 * celui de l'admin. Le lien vers une photo d'illustration passe désormais par une
 * clé étrangère réelle, avec ON DELETE SET NULL : supprimer une photo ne doit pas
 * emporter l'article qui l'illustrait.
 */
class CreateEditorial extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('articles', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('chapeau', 'text', ['null' => true])
            ->addColumn('contenu', 'text', ['limit' => 16777215, 'null' => true])
            ->addColumn('photo_id', 'integer', ['null' => true])
            ->addColumn('auteur_id', 'integer', ['null' => true])
            ->addColumn('publie_le', 'datetime', ['null' => true])
            ->addColumn('actif', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('vues', 'integer', ['default' => 0, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_articles_slug'])
            ->addIndex(['actif', 'publie_le'], ['name' => 'idx_articles_publication'])
            ->addIndex(['titre', 'chapeau'], ['type' => 'fulltext', 'name' => 'ft_articles_recherche'])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_articles_photo',
            ])
            ->addForeignKey('auteur_id', 'users', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_articles_auteur',
            ])
            ->create();

        $this->table('commentaires', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('article_id', 'integer', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('auteur', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('contenu', 'text', ['null' => false])
            // Modération a priori : un commentaire n'apparaît qu'une fois validé.
            ->addColumn('valide', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('ip', 'string', ['limit' => 45, 'null' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['article_id', 'valide'], ['name' => 'idx_commentaires_article'])
            ->addForeignKey('article_id', 'articles', 'id', [
                'delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_commentaires_article',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_commentaires_user',
            ])
            ->create();

        $this->table('pages', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('contenu', 'text', ['limit' => 16777215, 'null' => true])
            ->addColumn('meta_description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_pages_slug'])
            ->create();

        $this->table('livres', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('photo_id', 'integer', ['null' => true])
            ->addColumn('prix', 'decimal', ['precision' => 8, 'scale' => 2, 'null' => true])
            ->addColumn('lien_achat', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('date_parution', 'date', ['null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_livres_slug'])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_livres_photo',
            ])
            ->create();

        $this->table('expositions', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('lieu', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('photo_id', 'integer', ['null' => true])
            ->addColumn('date_debut', 'date', ['null' => true])
            ->addColumn('date_fin', 'date', ['null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_expositions_slug'])
            ->addIndex(['date_debut'], ['name' => 'idx_expositions_date'])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_expositions_photo',
            ])
            ->create();

        $this->table('typetirages', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('nom', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_typetirages_slug'])
            ->create();

        $this->table('tirages', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('photo_id', 'integer', ['null' => false])
            ->addColumn('typetirage_id', 'integer', ['null' => true])
            ->addColumn('format', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('papier', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('prix', 'decimal', ['precision' => 8, 'scale' => 2, 'null' => true])
            ->addColumn('tirage_limite', 'integer', ['null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['photo_id'], ['name' => 'idx_tirages_photo'])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_tirages_photo',
            ])
            ->addForeignKey('typetirage_id', 'typetirages', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_tirages_type',
            ])
            ->create();

        $this->table('typevideos', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('nom', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_typevideos_slug'])
            ->create();

        $this->table('videos', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('typevideo_id', 'integer', ['null' => true])
            ->addColumn('photo_id', 'integer', ['null' => true])
            // Plateforme + identifiant plutôt qu'une URL brute : le rendu de
            // l'iframe est ainsi maîtrisé côté serveur.
            ->addColumn('plateforme', 'enum', [
                'values' => ['youtube', 'vimeo'], 'default' => 'youtube', 'null' => false,
            ])
            ->addColumn('video_ref', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('duree', 'integer', ['null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_videos_slug'])
            ->addForeignKey('typevideo_id', 'typevideos', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_videos_type',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_videos_photo',
            ])
            ->create();

        $this->table('modeles', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('nom', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('photo_id', 'integer', ['null' => true])
            ->addColumn('instagram', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('site', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_modeles_slug'])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_modeles_photo',
            ])
            ->create();

        $this->table('shootings', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('modele_id', 'integer', ['null' => true])
            ->addColumn('photo_id', 'integer', ['null' => true])
            ->addColumn('album_id', 'integer', ['null' => true])
            ->addColumn('date_shooting', 'date', ['null' => true])
            ->addColumn('lieu', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_shootings_slug'])
            ->addForeignKey('modele_id', 'modeles', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_shootings_modele',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_shootings_photo',
            ])
            ->addForeignKey('album_id', 'albums', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_shootings_album',
            ])
            ->create();
    }
}
