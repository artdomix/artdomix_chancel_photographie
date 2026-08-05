<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Vérifie que le front public répond, et surtout qu'il ne laisse pas filtrer ce
 * qui doit rester privé.
 *
 * Ces tests tournent sur MySQL comme le reste du projet : les requêtes utilisent
 * des index FULLTEXT et des ENUM qu'un autre moteur n'aurait pas.
 */
class FrontPublicTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @return void
     */
    public function testPagesPubliquesRepondent(): void
    {
        foreach (['/', '/portfolio', '/recherche', '/carte', '/blog', '/contact'] as $url) {
            $this->get($url);
            $this->assertResponseOk(sprintf('La page %s devrait répondre.', $url));
        }
    }

    /**
     * Le point central du modèle de sécurité : une action non déclarée publique
     * doit être inaccessible, pas ouverte.
     *
     * @return void
     */
    public function testZonesProtegeesRedirigentVersLaConnexion(): void
    {
        foreach (['/admin', '/membre'] as $url) {
            $this->get($url);
            $this->assertRedirectContains('/connexion', sprintf('%s devrait exiger une connexion.', $url));
        }
    }

    /**
     * @return void
     */
    public function testPageDeConnexionAccessible(): void
    {
        $this->get('/connexion');

        $this->assertResponseOk();
        $this->assertResponseContains('Connexion');
    }

    /**
     * @return void
     */
    public function testUrlInconnueRenvoieUne404(): void
    {
        $this->get('/portfolio/album-qui-nexiste-pas');

        $this->assertResponseCode(404);
    }
}
