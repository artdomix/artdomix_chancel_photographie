<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
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
        $urls = [
            '/', '/portfolio', '/recherche', '/carte', '/blog', '/contact',
            // Rubriques éditoriales : leurs URL sont déclarées à la main dans
            // routes.php, une faute de frappe ne se verrait qu'ici.
            '/livres', '/tirages', '/expositions', '/videos',
            '/sitemap.xml', '/robots.txt', '/rss',
        ];

        foreach ($urls as $url) {
            $this->get($url);
            $this->assertResponseOk(sprintf('La page %s devrait répondre.', $url));
        }
    }

    /**
     * Les fiches éditoriales, sur des enregistrements créés pour l'occasion :
     * les listes répondent même vides, pas les fiches.
     *
     * @return void
     */
    public function testFichesEditorialesRepondent(): void
    {
        $slug = 'fiche-de-test-editorial';
        $cibles = [
            'Livres' => ['titre' => 'Fiche de test', 'slug' => $slug, 'actif' => true],
            'Expositions' => ['titre' => 'Fiche de test', 'slug' => $slug, 'actif' => true],
            'Videos' => [
                'titre' => 'Fiche de test',
                'slug' => $slug,
                'actif' => true,
                'plateforme' => 'youtube',
                'video_ref' => 'aqz-KE-bpKQ',
            ],
        ];

        foreach ($cibles as $nom => $donnees) {
            $table = TableRegistry::getTableLocator()->get($nom);
            $table->deleteAll(['slug' => $slug]);
            $table->saveOrFail($table->newEntity($donnees));

            $url = '/' . strtolower($nom) . '/' . $slug;
            $this->get($url);
            $this->assertResponseOk(sprintf('La fiche %s devrait répondre.', $url));

            $table->deleteAll(['slug' => $slug]);
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

    /**
     * Un jeton CSRF périmé — cas typique d'un onglet resté ouvert, ou d'un
     * changement de `Security.salt` qui invalide tous les cookies déjà posés —
     * doit renvoyer au formulaire avec un message, pas afficher une page 403.
     *
     * @return void
     */
    public function testJetonCsrfPerimeRenvoieAuFormulaire(): void
    {
        // Cookie et champ volontairement incohérents : c'est ce que voit le
        // serveur quand le cookie a été signé avec un autre salt.
        $this->configRequest([
            'cookies' => ['csrfToken' => 'jeton-perime-et-invalide'],
        ]);

        $this->post('/connexion', [
            '_csrfToken' => 'jeton-perime-et-invalide',
            'email' => 'inconnu@test.local',
            'password' => 'peu importe',
        ]);

        $this->assertRedirectContains('/connexion');
        $this->assertSession(
            'Votre session a expiré pour des raisons de sécurité. Merci de renvoyer le formulaire.',
            'Flash.flash.0.message',
        );
    }

    /**
     * En revanche une requête htmx doit recevoir le refus tel quel : rediriger
     * un fragment n'aurait aucun sens pour l'appelant.
     *
     * @return void
     */
    public function testJetonCsrfPerimeEnHtmxResteUnRefus(): void
    {
        $this->configRequest([
            'cookies' => ['csrfToken' => 'jeton-perime-et-invalide'],
            'headers' => ['HX-Request' => 'true'],
        ]);

        $this->post('/connexion', [
            '_csrfToken' => 'jeton-perime-et-invalide',
            'email' => 'inconnu@test.local',
            'password' => 'peu importe',
        ]);

        $this->assertResponseCode(403);
    }
}
