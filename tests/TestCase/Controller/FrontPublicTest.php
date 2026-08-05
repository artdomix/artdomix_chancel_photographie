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

    /**
     * @return void
     */
    public function testPlanDeSiteEtRobotsRepondent(): void
    {
        $this->get('/sitemap.xml');
        $this->assertResponseOk();
        $this->assertResponseContains('<urlset');

        $this->get('/robots.txt');
        $this->assertResponseOk();
        $this->assertResponseContains('Disallow: /admin');
    }

    /**
     * Le plan de site ne doit jamais exposer une URL privée : ce serait donner
     * aux moteurs la liste des liens de partage.
     *
     * @return void
     */
    public function testPlanDeSiteNExposePasLesUrlPrivees(): void
    {
        $this->get('/sitemap.xml');

        $this->assertResponseNotContains('/m/');
        $this->assertResponseNotContains('/g/');
        $this->assertResponseNotContains('/admin');
        $this->assertResponseNotContains('/membre');
    }

    /**
     * @return void
     */
    public function testFluxRssEstValide(): void
    {
        $this->get('/rss');

        $this->assertResponseOk();
        $this->assertResponseContains('<rss version="2.0"');
        $this->assertResponseContains('<channel>');
    }
}
