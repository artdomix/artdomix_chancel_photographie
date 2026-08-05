<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    /*
     * Back-office d'administration : /admin/*
     */
    $routes->prefix('Admin', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Tableau', 'action' => 'index']);
        $builder->fallbacks(DashedRoute::class);
    });

    /*
     * Espace membre (clients) : /membre/*
     */
    $routes->prefix('Membre', ['path' => '/membre'], function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Tableau', 'action' => 'index']);
        $builder->fallbacks(DashedRoute::class);
    });

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'accueil']);

        /*
         * Comptes. `/admin` mène au tableau de bord, et le middleware redirige
         * ici tant que le visiteur n'est pas connecté : l'URL que le photographe
         * a dans ses favoris depuis des années continue donc de fonctionner.
         */
        $builder->connect('/connexion', ['controller' => 'Utilisateurs', 'action' => 'connexion']);
        $builder->connect('/deconnexion', ['controller' => 'Utilisateurs', 'action' => 'deconnexion']);
        $builder->connect('/mot-de-passe-oublie', ['controller' => 'Utilisateurs', 'action' => 'motDePasseOublie']);
        $builder->connect('/reinitialiser/*', ['controller' => 'Utilisateurs', 'action' => 'reinitialiser']);

        /*
         * Liens de partage. Le jeton est opaque et ne laisse rien deviner du
         * contenu ni de son identifiant en base.
         */
        $builder->connect('/m/deverrouiller/*', ['controller' => 'Moodboards', 'action' => 'deverrouiller']);
        $builder->connect('/m/commenter/*', ['controller' => 'Moodboards', 'action' => 'commenter']);
        $builder->connect('/m/*', ['controller' => 'Moodboards', 'action' => 'partage']);
        $builder->connect('/g/deverrouiller/*', ['controller' => 'Galeries', 'action' => 'deverrouiller']);
        $builder->connect('/g/favori/*', ['controller' => 'Galeries', 'action' => 'favori']);
        $builder->connect('/g/telecharger/*', ['controller' => 'Galeries', 'action' => 'telecharger']);
        $builder->connect('/g/*', ['controller' => 'Galeries', 'action' => 'partage']);

        /*
         * Portfolio. Le `*` accepte un chemin imbriqué (`/portfolio/voyage/japon`)
         * pour refléter l'arbre des albums dans l'URL.
         */
        $builder->connect('/portfolio', ['controller' => 'Portfolio', 'action' => 'index']);
        $builder->connect('/portfolio/*', ['controller' => 'Portfolio', 'action' => 'album']);
        $builder->connect('/photo/*', ['controller' => 'Portfolio', 'action' => 'photo']);
        $builder->connect('/tag/*', ['controller' => 'Portfolio', 'action' => 'tag']);
        $builder->connect('/recherche', ['controller' => 'Portfolio', 'action' => 'recherche']);
        $builder->connect('/carte', ['controller' => 'Portfolio', 'action' => 'carte']);

        $builder->connect('/blog', ['controller' => 'Articles', 'action' => 'index']);
        $builder->connect('/blog/*', ['controller' => 'Articles', 'action' => 'voir']);
        $builder->connect('/contact', ['controller' => 'Messages', 'action' => 'contact']);

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * It is NOT recommended to use fallback routes after your initial prototyping phase!
         * See https://book.cakephp.org/5/en/development/routing.html#fallbacks-method for more information
         */
        $builder->fallbacks();
    });

    /*
     * If you need a different set of middleware or none at all,
     * open new scope and define routes there.
     *
     * ```
     * $routes->scope('/api', function (RouteBuilder $builder): void {
     *     // No $builder->applyMiddleware() here.
     *
     *     // Parse specified extensions from URLs
     *     // $builder->setExtensions(['json', 'xml']);
     *
     *     // Connect API actions here.
     * });
     * ```
     */
};
