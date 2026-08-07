<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Chapô éditorial des albums.
 *
 * `description` porte déjà le texte d'intention d'une série, mais un texte long
 * ne tient pas dans une liste ni dans un aperçu de partage. Le chapô joue ce rôle
 * de résumé court, comme il le fait déjà pour les articles : les deux champs se
 * complètent, ils ne se remplacent pas.
 */
class AjouterChapeauAlbums extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('albums')
            ->addColumn('chapeau', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'nom',
                'comment' => 'Résumé court affiché en tête de série et dans les aperçus',
            ])
            ->update();
    }
}
