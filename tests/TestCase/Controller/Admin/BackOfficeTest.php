<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Enum\Role;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Le back-office doit répondre en entier.
 *
 * Ces tests existent surtout comme filet : le menu du layout renvoie vers six
 * contrôleurs, et deux d'entre eux ont longtemps manqué sans que rien ne le
 * signale — la page tombait en 404 au clic. Un lien de menu sans test est un
 * lien qui casse en silence.
 */
class BackOfficeTest extends TestCase
{
    use IntegrationTestTrait;

    protected const EMAIL_ADMIN = 'admin-backoffice@test.local';

    protected const EMAIL_MEMBRE = 'membre-backoffice@test.local';

    protected int $idAdmin;

    protected int $idMembre;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email IN' => [self::EMAIL_ADMIN, self::EMAIL_MEMBRE]]);

        $admin = $users->newEntity([
            'email' => self::EMAIL_ADMIN,
            'password' => 'mot-de-passe-de-test-1',
            'nom' => 'Admin',
        ]);
        // `role` n'est volontairement pas assignable en masse.
        $admin->role = Role::Admin;
        $users->saveOrFail($admin);
        $this->idAdmin = $admin->id;

        $membre = $users->newEntity([
            'email' => self::EMAIL_MEMBRE,
            'password' => 'mot-de-passe-de-test-2',
            'nom' => 'Membre',
        ]);
        $membre->role = Role::Membre;
        $users->saveOrFail($membre);
        $this->idMembre = $membre->id;
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Users')
            ->deleteAll(['email IN' => [self::EMAIL_ADMIN, self::EMAIL_MEMBRE]]);

        parent::tearDown();
    }

    /**
     * @param int $id Identifiant du compte à simuler.
     * @return void
     */
    protected function connecter(int $id): void
    {
        $this->session(['Auth' => TableRegistry::getTableLocator()->get('Users')->get($id)]);
    }

    /**
     * Toutes les entrées du menu du back-office, sans exception.
     *
     * @return void
     */
    public function testToutesLesEntreesDuMenuRepondent(): void
    {
        $this->connecter($this->idAdmin);

        $urls = [
            '/admin',
            '/admin/photos',
            '/admin/photos/ajouter',
            '/admin/albums',
            '/admin/moodboards',
            '/admin/moodboards/modifier',
            '/admin/galeries',
            '/admin/galeries/modifier',
            '/admin/messages',
        ];

        foreach ($urls as $url) {
            $this->get($url);
            $this->assertResponseOk(sprintf('%s devrait répondre.', $url));
        }
    }

    /**
     * Les filtres de la liste des messages sont des URL distinctes : elles
     * doivent répondre aussi.
     *
     * @return void
     */
    public function testFiltresDesMessagesRepondent(): void
    {
        $this->connecter($this->idAdmin);

        foreach (['tous', 'non-lus', 'a-traiter'] as $filtre) {
            $this->get('/admin/messages?filtre=' . $filtre);
            $this->assertResponseOk(sprintf('Le filtre %s devrait répondre.', $filtre));
        }
    }

    /**
     * Les écrans d'une galerie existante — dont la liste des favoris, qui repose
     * sur une jointure que rien d'autre n'exerce.
     *
     * @return void
     */
    public function testEcransDUneGalerieExistante(): void
    {
        $this->connecter($this->idAdmin);

        $galeries = TableRegistry::getTableLocator()->get('Galeries');
        $galeries->deleteAll(['nom' => 'Galerie de test back-office']);

        $galerie = $galeries->newEntity([
            'nom' => 'Galerie de test back-office',
            'client_id' => $this->idMembre,
        ]);
        $galeries->saveOrFail($galerie);

        foreach (['modifier', 'favoris'] as $action) {
            $this->get(sprintf('/admin/galeries/%s/%d', $action, $galerie->id));
            $this->assertResponseOk(sprintf('%s devrait répondre.', $action));
        }

        $galeries->delete($galerie);
    }

    /**
     * Un membre connecté n'est pas un administrateur : il doit recevoir un
     * refus, pas la page.
     *
     * @return void
     */
    public function testUnMembreNAccedePasAuBackOffice(): void
    {
        $this->connecter($this->idMembre);

        foreach (['/admin', '/admin/galeries', '/admin/messages'] as $url) {
            $this->get($url);
            $this->assertResponseCode(403, sprintf('%s devrait être refusé à un membre.', $url));
        }
    }

    /**
     * @return void
     */
    public function testVisiteurAnonymeEstRedirigeVersLaConnexion(): void
    {
        foreach (['/admin/galeries', '/admin/messages'] as $url) {
            $this->get($url);
            $this->assertRedirectContains('/connexion', sprintf('%s devrait exiger une connexion.', $url));
        }
    }
}
