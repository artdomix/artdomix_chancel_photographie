<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Moodboards : sélections de photos partageables par lien.
 *
 * Trois niveaux de visibilité :
 *  - `public` : listé et accessible librement ;
 *  - `lien`   : accessible à qui possède le jeton, non listé ;
 *  - `prive`  : jeton + mot de passe.
 *
 * Le mot de passe est propre au moodboard et hashé comme un mot de passe de
 * compte : il transite par le même formulaire public, il mérite le même soin.
 */
class CreateMoodboards extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $moodboards = $this->table('moodboards', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Moodboards partageables',
        ]);

        $moodboards
            ->addColumn('titre', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            // Thème d'animation GSAP appliqué au rendu public.
            ->addColumn('theme', 'enum', [
                'values' => ['mosaique-flip', 'mur-parallaxe', 'carrousel-pinne', 'grille-cinetique'],
                'default' => 'mosaique-flip',
                'null' => false,
            ])
            ->addColumn('visibilite', 'enum', [
                'values' => ['prive', 'lien', 'public'],
                'default' => 'lien',
                'null' => false,
            ])
            // Jeton opaque tiré au sort : c'est lui qui figure dans l'URL partagée,
            // jamais l'identifiant numérique (qui serait énumérable).
            ->addColumn('share_token', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('expires_at', 'datetime', ['null' => true])
            // Propriétaire (admin). Le moodboard survit à la suppression du compte.
            ->addColumn('user_id', 'integer', ['null' => true])
            // Membre destinataire, quand le moodboard est adressé à un client précis.
            ->addColumn('destinataire_id', 'integer', ['null' => true])
            ->addColumn('commentaires_actifs', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('vues', 'integer', ['default' => 0, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['share_token'], ['unique' => true, 'name' => 'idx_moodboards_token'])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_moodboards_slug'])
            ->addIndex(['visibilite'], ['name' => 'idx_moodboards_visibilite'])
            ->addIndex(['destinataire_id'], ['name' => 'idx_moodboards_destinataire'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_moodboards_user',
            ])
            ->addForeignKey('destinataire_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_moodboards_destinataire',
            ])
            ->create();

        $liaison = $this->table('moodboards_photos', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Photos composant un moodboard',
        ]);

        $liaison
            ->addColumn('moodboard_id', 'integer', ['null' => false])
            ->addColumn('photo_id', 'integer', ['null' => false])
            ->addColumn('ordre', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            // Certains thèmes donnent plus de place aux photos mises en avant.
            ->addColumn('mise_en_avant', 'boolean', ['default' => false, 'null' => false])
            ->addIndex(['moodboard_id', 'photo_id'], [
                'unique' => true,
                'name' => 'idx_moodboards_photos_unique',
            ])
            ->addIndex(['moodboard_id', 'ordre'], ['name' => 'idx_moodboards_photos_ordre'])
            ->addForeignKey('moodboard_id', 'moodboards', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_moodboards_photos_moodboard',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_moodboards_photos_photo',
            ])
            ->create();

        $commentaires = $this->table('moodboard_commentaires', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Retours des membres sur un moodboard',
        ]);

        $commentaires
            ->addColumn('moodboard_id', 'integer', ['null' => false])
            // Nullable : un visiteur arrivé par le lien de partage n'a pas de compte.
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('photo_id', 'integer', ['null' => true])
            ->addColumn('auteur', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('contenu', 'text', ['null' => false])
            ->addColumn('valide', 'boolean', ['default' => false, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['moodboard_id', 'created'], ['name' => 'idx_mb_commentaires_moodboard'])
            ->addForeignKey('moodboard_id', 'moodboards', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_mb_commentaires_moodboard',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_mb_commentaires_user',
            ])
            ->addForeignKey('photo_id', 'photos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_mb_commentaires_photo',
            ])
            ->create();
    }
}
