<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Model\Enum\Role;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Le menu principal du site public.
 *
 * Un lien de menu qui mène nulle part ne se manifeste qu'au clic. Ces tests
 * confrontent donc chaque entrée affichée aux routes réellement servies, et
 * vérifient que le visiteur sait toujours où il se trouve.
 */
class NavigationHelperTest extends TestCase
{
    use IntegrationTestTrait;

    protected const EMAIL_MEMBRE = 'membre-menu@test.local';

    protected const EMAIL_ADMIN = 'admin-menu@test.local';

    protected const FICHIER_PHOTO = 'photo-navigation';

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Users')
            ->deleteAll(['email IN' => [self::EMAIL_MEMBRE, self::EMAIL_ADMIN]]);

        parent::tearDown();
    }

    /**
     * @param string $email Adresse du compte.
     * @param \App\Model\Enum\Role $role Rôle voulu.
     * @return void
     */
    protected function connecter(string $email, Role $role): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email' => $email]);

        $compte = $users->newEntity([
            'email' => $email,
            'password' => 'mot-de-passe-de-test',
            'nom' => 'Testeur',
        ]);
        $compte->role = $role;
        $users->saveOrFail($compte);

        $this->session(['Auth' => $users->get($compte->id)]);
    }

    /**
     * Extrait les cibles du menu principal de la page rendue.
     *
     * @return list<string>
     */
    protected function liensDuMenu(): array
    {
        preg_match(
            '#<nav id="menu-principal".*?</nav>#s',
            (string)$this->_response->getBody(),
            $menu,
        );

        $this->assertNotEmpty($menu, 'La page devrait porter un menu principal.');

        preg_match_all('#href="([^"]+)"#', $menu[0], $liens);

        return $liens[1];
    }

    /**
     * Chaque entrée du menu doit mener à une page qui répond.
     *
     * C'est le test central : le menu est la seule porte d'entrée du site, une
     * rubrique injoignable y passerait inaperçue jusqu'au premier visiteur.
     *
     * @return void
     */
    public function testChaqueEntreeDuMenuMeneAUnePageQuiRepond(): void
    {
        $this->get('/');
        $this->assertResponseOk();

        $liens = $this->liensDuMenu();
        $this->assertNotEmpty($liens);

        foreach ($liens as $lien) {
            $this->get($lien);
            $this->assertResponseSuccess(sprintf('Le lien %s du menu devrait mener quelque part.', $lien));
        }
    }

    /**
     * Toutes les rubriques publiques doivent être atteignables depuis le menu.
     *
     * @return void
     */
    public function testLeMenuCouvreLesRubriquesPubliques(): void
    {
        $this->get('/');

        $liens = $this->liensDuMenu();

        foreach (
            [
            '/portfolio', '/tirages', '/livres', '/expositions',
            '/videos', '/carte', '/recherche', '/blog', '/contact',
            ] as $attendu
        ) {
            $this->assertContains($attendu, $liens, sprintf('%s devrait figurer au menu.', $attendu));
        }
    }

    /**
     * Un visiteur anonyme doit trouver l'entrée de son espace : sans ce lien, un
     * client détenteur d'un compte devait connaître l'URL par cœur.
     *
     * @return void
     */
    public function testLeMenuOffreLaConnexionAuVisiteurAnonyme(): void
    {
        $this->get('/');

        $this->assertContains('/connexion', $this->liensDuMenu());
        $this->assertResponseContains('Connexion');
    }

    /**
     * Connecté, le menu mène à l'espace correspondant au rôle, et propose la
     * déconnexion.
     *
     * @return void
     */
    public function testLeMenuSAdapteAuCompteConnecte(): void
    {
        $this->connecter(self::EMAIL_MEMBRE, Role::Membre);
        $this->get('/');
        $liens = $this->liensDuMenu();

        $this->assertContains('/membre', $liens, 'Un membre doit atteindre son espace.');
        $this->assertContains('/deconnexion', $liens);
        $this->assertNotContains('/connexion', $liens, 'Inutile de proposer de se connecter deux fois.');

        $this->connecter(self::EMAIL_ADMIN, Role::Admin);
        $this->get('/');
        $liens = $this->liensDuMenu();

        $this->assertContains('/admin', $liens, 'Un administrateur doit atteindre son back-office.');
        $this->assertNotContains('/membre', $liens);
    }

    /**
     * La rubrique courante doit être signalée, y compris sur les pages qui n'en
     * portent pas le préfixe : une photo isolée relève du portfolio.
     *
     * @return void
     */
    public function testLaRubriqueCouranteEstSignalee(): void
    {
        $this->get('/blog');
        $this->assertResponseOk();
        $this->assertMatchesRegularExpression(
            '#<a[^>]*aria-current="page"[^>]*href="/blog"#s',
            (string)$this->_response->getBody(),
            'Le blog devrait être marqué comme rubrique courante.',
        );

        // Une seule rubrique à la fois, sinon le repère ne repère plus rien.
        $this->assertSame(
            1,
            substr_count((string)$this->_response->getBody(), 'aria-current="page"'),
            'Une seule rubrique doit être marquée courante.',
        );

        // L'accueil n'est pas une rubrique du menu : aucune ne doit s'allumer.
        $this->get('/');
        $this->assertStringNotContainsString('aria-current="page"', (string)$this->_response->getBody());
    }

    /**
     * Une photo ou un tag relèvent du portfolio : sans ce rattachement, ouvrir
     * une photo éteindrait tout le menu.
     *
     * @return void
     */
    public function testUnePhotoEstRattacheeAuPortfolio(): void
    {
        $photos = TableRegistry::getTableLocator()->get('Photos');
        $photos->deleteAll(['fichier' => self::FICHIER_PHOTO]);

        $photo = $photos->newEntity(['titre' => 'Photo de navigation']);
        $photo->set('uuid', Text::uuid());
        $photo->set('fichier', self::FICHIER_PHOTO);
        $photo->set('extension_origine', 'jpg');
        $photo->set('largeur', 1600);
        $photo->set('hauteur', 1067);
        $photo->set('actif', true);
        $photos->saveOrFail($photo);

        $this->get('/photo/' . $photo->slug);
        $this->assertResponseOk();
        $this->assertMatchesRegularExpression(
            '#<a[^>]*aria-current="page"[^>]*href="/portfolio"#s',
            (string)$this->_response->getBody(),
            'Une photo devrait allumer la rubrique Portfolio.',
        );

        $photos->deleteAll(['fichier' => self::FICHIER_PHOTO]);
    }
}
