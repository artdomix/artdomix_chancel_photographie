<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use App\Middleware\CsrfExpireMiddleware;
use App\Middleware\HostHeaderMiddleware;
use App\Service\Image\DerivativeGenerator;
use App\Service\Image\ExifReader;
use App\Service\Image\PhotoUploader;
use App\Service\Image\UploadValidator;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Identifier\PasswordIdentifier;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\OrmResolver;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\TableRegistry;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 *
 * @extends \Cake\Http\BaseApplication<\App\Application>
 */
class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        // By default, does not allow fallback classes.
        FactoryLocator::add(
            'Table',
            (new TableLocator())->allowFallbackClass(false),
        );
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))

            // Validate Host header to prevent Host Header Injection attacks.
            // In production, ensures App.fullBaseUrl is configured and validates
            // the incoming Host header against it.
            ->add(new HostHeaderMiddleware())

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance.
            // See https://github.com/CakeDC/cakephp-cached-routing
            ->add(new RoutingMiddleware($this))

            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData()
            // https://book.cakephp.org/5/en/controllers/middleware.html#body-parser-middleware
            ->add(new BodyParserMiddleware())

            // Cross Site Request Forgery (CSRF) Protection Middleware
            // https://book.cakephp.org/5/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
            // Placé AVANT le CSRF pour intercepter son exception : un jeton
            // périmé doit renvoyer au formulaire, pas afficher une page 403.
            ->add(new CsrfExpireMiddleware())

            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
                // Explicite plutôt que laissé à null : les navigateurs
                // appliquent Lax par défaut, mais l'écrire aligne le cookie CSRF
                // sur le cookie de session et rend le comportement lisible.
                'samesite' => 'Lax',
            ]))

            // L'ordre compte : l'authentification identifie le visiteur, puis
            // l'autorisation décide. Les deux sont montées ici, globalement, pour
            // qu'aucune zone ne puisse être oubliée. C'est l'inverse de l'ancien
            // site, qui ouvrait tout puis refermait sur le préfixe `admin` — un
            // oubli y exposait la page au lieu de la fermer.
            ->add(new AuthenticationMiddleware($this))
            ->add(new AuthorizationMiddleware($this));

        return $middlewareQueue;
    }

    /**
     * Service d'authentification : qui est le visiteur ?
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Requête courante.
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $urlConnexion = Router::url([
            'prefix' => false,
            'plugin' => false,
            'controller' => 'Utilisateurs',
            'action' => 'connexion',
        ]);

        $service = new AuthenticationService([
            'unauthenticatedRedirect' => $urlConnexion,
            'queryParam' => 'redirect',
            // Désactivée par défaut dans le plugin pour raisons de compatibilité.
            // Sans elle, un `?redirect=https://ailleurs` renverrait le visiteur
            // hors du site après connexion — un tremplin de hameçonnage idéal.
            'redirectValidation' => ['enabled' => true],
        ]);

        $champs = [
            PasswordIdentifier::CREDENTIAL_USERNAME => 'email',
            PasswordIdentifier::CREDENTIAL_PASSWORD => 'password',
        ];

        $identifiant = [
            'className' => 'Authentication.Password',
            'fields' => $champs,
            'resolver' => [
                'className' => 'Authentication.Orm',
                'userModel' => 'Users',
                // Un compte désactivé ne doit plus pouvoir se connecter, même
                // avec le bon mot de passe.
                'finder' => 'actifs',
            ],
        ];

        // La session passe en premier : sur une navigation normale, inutile de
        // retoucher la base à chaque requête.
        $service->loadAuthenticator('Authentication.Session', [
            'fields' => $champs,
            'identifier' => $identifiant,
        ]);
        $service->loadAuthenticator('Authentication.Form', [
            'fields' => $champs,
            'loginUrl' => $urlConnexion,
            'identifier' => $identifiant,
        ]);

        return $service;
    }

    /**
     * Service d'autorisation : ce visiteur a-t-il le droit ?
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Requête courante.
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        // Résout chaque entité vers la policy correspondante dans src/Policy/.
        return new AuthorizationService(new OrmResolver());
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        // Les réglages du filigrane vivent en base (table `config`) pour que le
        // photographe puisse les changer sans toucher au code. On les lit ici,
        // au moment de construire le service.
        $container->add(DerivativeGenerator::class, function (): DerivativeGenerator {
            return new DerivativeGenerator(WWW_ROOT . 'media' . DS . 'photos', $this->reglagesImage());
        });

        $container->add(ExifReader::class);
        $container->add(UploadValidator::class);

        $container->add(PhotoUploader::class)
            ->addArgument(ROOT . DS . 'storage' . DS . 'originaux')
            ->addArgument(DerivativeGenerator::class)
            ->addArgument(ExifReader::class)
            ->addArgument(UploadValidator::class);
    }

    /**
     * Réglages d'image lus depuis la table `config`.
     *
     * Encapsulé dans un try/catch : les commandes console peuvent tourner avant
     * que la base n'existe (première installation, `migrations migrate`), et
     * l'absence de réglage ne doit pas empêcher l'application de démarrer.
     *
     * @return array<string, mixed>
     */
    protected function reglagesImage(): array
    {
        try {
            $table = TableRegistry::getTableLocator()->get('Config');
            $lignes = $table->find()->where(['cle LIKE' => 'images.%'])->all();

            $reglages = [];

            foreach ($lignes as $ligne) {
                $reglages[str_replace('images.', '', $ligne->cle)] = $ligne->valeur;
            }

            $reglages['filigrane'] = !empty($reglages['filigrane']) && $reglages['filigrane'] !== '0';

            return $reglages;
        } catch (Throwable) {
            return ['filigrane' => false];
        }
    }

    /**
     * Register custom event listeners here
     *
     * @param \Cake\Event\EventManagerInterface $eventManager
     * @return \Cake\Event\EventManagerInterface
     * @link https://book.cakephp.org/5/en/core-libraries/events.html#registering-listeners
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        // $eventManager->on(new SomeCustomListenerClass());

        return $eventManager;
    }
}
