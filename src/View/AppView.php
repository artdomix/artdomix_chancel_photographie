<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use Cake\View\View;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 */
class AppView extends View
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like adding helpers.
     *
     * e.g. `$this->addHelper('Html');`
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->addHelper('Assets');
        $this->addHelper('Photo');
        $this->addHelper('Seo');
        $this->addHelper('Navigation');
        $this->addHelper('Captcha.Captcha');
    }

    /**
     * Reporte le titre posé par le contrôleur dans le bloc `title`.
     *
     * Les contrôleurs écrivent `$this->set('title', …)`, qui crée une variable de
     * vue — pas le bloc que les layouts lisent avec `fetch('title')`. Sans ce
     * report, CakePHP remplit le bloc lui-même avec le chemin du template
     * humanisé et chaque page sort avec un `<title>` du genre « Pages » ou
     * « Portfolio ».
     *
     * Le report a lieu avant le rendu du template : un template qui assigne
     * lui-même le bloc reste prioritaire.
     *
     * @param string|null $template Template à rendre.
     * @param string|false|null $layout Layout à utiliser.
     * @return string
     */
    public function render(?string $template = null, string|false|null $layout = null): string
    {
        $titre = $this->get('title');

        if ($this->fetch('title') === '' && is_string($titre) && $titre !== '') {
            $this->assign('title', $titre);
        }

        return parent::render($template, $layout);
    }
}
