<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Le chargement des bibliothèques front et l'injection du thème Tailwind.
 *
 * Le projet n'a pas d'étape de build : Tailwind est compilé dans le navigateur,
 * à partir du bloc `<style type="text/tailwindcss">` que ce helper injecte. Ce
 * bloc est donc du code exécuté, et une erreur dedans ne se voit ni au `php -l`
 * ni au rendu HTML — seulement à l'écran, sous la forme d'une page sans styles.
 */
class AssetsHelperTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Le thème ne doit contenir aucune règle d'import, commentaires compris.
     *
     * Le compilateur navigateur ajoute lui-même la feuille Tailwind de base,
     * mais seulement s'il n'en trouve aucune dans la source — et il le vérifie
     * par une recherche de texte brute, qui ne distingue pas une règle d'un
     * commentaire. Vérifié dans Chromium avec le bundle réel : une source
     * contenant le mot dans un commentaire ne produit plus aucun utilitaire, et
     * une vraie règle d'import déclenche en prime une requête 404 relative à
     * l'URL courante (`/admin/tailwindcss`), le navigateur lisant ces règles
     * bien qu'il ignore le type du bloc.
     *
     * @return void
     */
    public function testLeThemeNeContientAucuneRegleDImport(): void
    {
        $theme = (string)file_get_contents(WWW_ROOT . 'css' . DS . 'chancel-theme.css');

        $this->assertStringNotContainsString('@import', $theme);
    }

    /**
     * Le thème est bien inline dans la page, et non référencé : un `<link>` ne
     * serait pas compilé, et la page s'afficherait sans styles.
     *
     * @return void
     */
    public function testLeThemeEstInjecteDansLaPage(): void
    {
        $this->get('/');
        $this->assertResponseOk();

        $this->assertResponseContains('<style type="text/tailwindcss">');
        // Une valeur du thème, pour prouver que c'est bien le fichier du projet
        // qui a été inline et pas un bloc vide.
        $this->assertResponseContains('--color-corail: #e85356;');
    }
}
