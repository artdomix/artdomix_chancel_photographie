<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Transforme un jeton CSRF périmé en message compréhensible.
 *
 * Le jeton CSRF est signé avec `Security.salt` : changer le salt — ce que fait
 * toute nouvelle installation, puisque `config/app_local.php` n'est pas
 * versionné — invalide d'un coup tous les cookies déjà posés dans les
 * navigateurs. Un onglet resté ouvert produit le même effet.
 *
 * Sans ce filtre, le visiteur tombe sur une page d'exception `403` qui ne lui
 * dit pas quoi faire. Ici il est renvoyé vers le formulaire avec un message et
 * un cookie neuf : il lui suffit de renvoyer sa saisie.
 *
 * La protection n'est pas affaiblie — la requête suspecte est bel et bien
 * rejetée, seule sa présentation change.
 */
class CsrfExpireMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Requête entrante.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Maillon suivant.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (InvalidCsrfTokenException $e) {
            // Une requête d'API ou htmx n'a que faire d'une redirection : elle
            // doit recevoir le refus tel quel pour que l'appelant le traite.
            if ($this->attendUneRedirection($request) === false) {
                throw $e;
            }

            $session = $request->getAttribute('session');
            $session?->write('Flash.flash', [[
                'message' => __(
                    'Votre session a expiré pour des raisons de sécurité. Merci de renvoyer le formulaire.',
                ),
                'key' => 'flash',
                'element' => 'flash/error',
                'params' => [],
            ]]);

            // Redirection vers la page d'où venait le formulaire : le visiteur
            // retrouve un jeton frais sans avoir à comprendre ce qui s'est passé.
            $cible = $request->getUri()->getPath();

            return (new Response())
                ->withStatus(302)
                ->withHeader('Location', $cible);
        }
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Requête entrante.
     * @return bool
     */
    protected function attendUneRedirection(ServerRequestInterface $request): bool
    {
        if ($request->getHeaderLine('HX-Request') === 'true') {
            return false;
        }

        $accept = $request->getHeaderLine('Accept');

        return str_contains($accept, 'text/html') || $accept === '' || $accept === '*/*';
    }
}
