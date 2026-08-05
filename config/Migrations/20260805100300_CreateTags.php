<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Tags transverses : ils traversent l'arbre d'albums.
 *
 * Le `type` distingue les familles de tags (sujet, lieu, matériel…) pour pouvoir
 * les présenter séparément dans la recherche sans multiplier les tables.
 */
class CreateTags extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $tags = $this->table('tags', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Mots-clés transverses',
        ]);

        $tags
            ->addColumn('nom', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('type', 'enum', [
                'values' => ['sujet', 'lieu', 'materiel', 'technique'],
                'default' => 'sujet',
                'null' => false,
            ])
            ->addColumn('description', 'text', ['null' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_tags_slug'])
            ->addIndex(['type'], ['name' => 'idx_tags_type'])
            ->addIndex(['nom'], ['type' => 'fulltext', 'name' => 'ft_tags_nom'])
            ->create();

        $liaison = $this->table('photos_tags', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Liaison N-N photos / tags',
        ]);

        $liaison
            ->addColumn('photo_id', 'integer', ['null' => false])
            ->addColumn('tag_id', 'integer', ['null' => false])
            ->addIndex(['photo_id', 'tag_id'], [
                'unique' => true,
                'name' => 'idx_photos_tags_unique',
            ])
            ->addIndex(['tag_id'], ['name' => 'idx_photos_tags_tag'])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_photos_tags_photo',
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_photos_tags_tag',
            ])
            ->create();
    }
}
