<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Formulaire de contact et réglages du site.
 *
 * `demandes` est la liste des natures de demande proposées dans le formulaire
 * (shooting, tirage, presse…), `messages` les messages reçus.
 */
class CreateContact extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('demandes', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('nom', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('ordre', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('actif', 'boolean', ['default' => true, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['slug'], ['unique' => true, 'name' => 'idx_demandes_slug'])
            ->create();

        $this->table('messages', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('demande_id', 'integer', ['null' => true])
            ->addColumn('nom', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('telephone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('sujet', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('contenu', 'text', ['null' => false])
            ->addColumn('lu', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('traite', 'boolean', ['default' => false, 'null' => false])
            // Conservée pour la lutte anti-spam. RGPD : à purger périodiquement,
            // une donnée qui ne sert plus n'a pas à rester en base.
            ->addColumn('ip', 'string', ['limit' => 45, 'null' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['lu', 'created'], ['name' => 'idx_messages_suivi'])
            ->addForeignKey('demande_id', 'demandes', 'id', [
                'delete' => 'SET_NULL', 'update' => 'CASCADE', 'constraint' => 'fk_messages_demande',
            ])
            ->create();

        // Réglages éditables depuis l'admin (coordonnées, réseaux, filigrane…),
        // stockés en paires clé/valeur pour éviter une migration par réglage.
        $this->table('config', ['collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('cle', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('valeur', 'text', ['null' => true])
            ->addColumn('libelle', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('type', 'enum', [
                'values' => ['texte', 'nombre', 'booleen', 'html'],
                'default' => 'texte',
                'null' => false,
            ])
            ->addTimestamps('created', 'modified')
            ->addIndex(['cle'], ['unique' => true, 'name' => 'idx_config_cle'])
            ->create();
    }
}
