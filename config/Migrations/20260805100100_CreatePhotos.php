<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Le cœur du site : les photos et leurs métadonnées EXIF.
 *
 * `photos` ne porte plus de `album_id` ni de `categorie_id` : le rattachement se
 * fait par les tables de liaison `albums_photos` et `photos_tags`. Une photo peut
 * donc apparaître dans plusieurs albums, ce qui était impossible avant.
 */
class CreatePhotos extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $photos = $this->table('photos', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Photos et leurs dérivés',
        ]);

        $photos
            // Identifiant public, utilisé pour nommer les fichiers sur disque.
            // Remplace le md5(nom) de l'ancien site et sa boucle anti-collision.
            ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            // Texte alternatif : obligatoire pour l'accessibilité et le SEO image.
            ->addColumn('alt', 'string', ['limit' => 255, 'null' => true])
            // Nom de base des dérivés, sans extension ni suffixe de variante.
            ->addColumn('fichier', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('extension_origine', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('largeur', 'integer', ['null' => true])
            ->addColumn('hauteur', 'integer', ['null' => true])
            ->addColumn('poids_octets', 'integer', ['null' => true])
            // Couleur dominante et miniature encodée : évitent le flash blanc et le
            // décalage de mise en page pendant le chargement des grandes images.
            ->addColumn('couleur_dominante', 'string', ['limit' => 7, 'null' => true])
            ->addColumn('lqip', 'text', ['null' => true])
            // L'AVIF dépend du GD de l'hébergeur : on note ce qui existe réellement
            // sur disque plutôt que de référencer un fichier peut-être absent.
            ->addColumn('has_avif', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('has_webp', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('filigrane', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_photos_slug'])
            ->addIndex(['uuid'], ['unique' => true, 'name' => 'idx_photos_uuid'])
            ->addIndex(['actif'], ['name' => 'idx_photos_actif'])
            ->addIndex(['titre', 'description'], [
                'type' => 'fulltext',
                'name' => 'ft_photos_recherche',
            ])
            ->create();

        $exifs = $this->table('exifs', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Métadonnées EXIF, une ligne par photo',
        ]);

        $exifs
            ->addColumn('photo_id', 'integer', ['null' => false])
            ->addColumn('apn', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('objectif', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('ouverture', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('iso', 'integer', ['null' => true])
            ->addColumn('exposition', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('focale', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('artiste', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('copyright', 'string', ['limit' => 190, 'null' => true])
            // Sert au tri chronologique des galeries, comme sur l'ancien site.
            ->addColumn('date_capture', 'datetime', ['null' => true])
            // Coordonnées décimales issues des tags GPS, pour la carte des séries voyage.
            ->addColumn('gps_lat', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('gps_lng', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('gps_altitude', 'decimal', ['precision' => 8, 'scale' => 2, 'null' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['photo_id'], ['unique' => true, 'name' => 'idx_exifs_photo'])
            ->addIndex(['date_capture'], ['name' => 'idx_exifs_date_capture'])
            ->addIndex(['gps_lat', 'gps_lng'], ['name' => 'idx_exifs_gps'])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_exifs_photo',
            ])
            ->create();
    }
}
