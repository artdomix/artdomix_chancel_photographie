<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Enum\Role;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Le back-office doit répondre en entier.
 *
 * Ces tests existent surtout comme filet : le menu du layout renvoie vers une
 * vingtaine de contrôleurs, et plusieurs d'entre eux ont longtemps manqué sans
 * que rien ne le signale — la page tombait en 404 ou en 500 au clic. Un lien de
 * menu sans test est un lien qui casse en silence.
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
     * Sections listées dans le menu du back-office : segment d'URL, et si la
     * section accepte la création d'un enregistrement.
     *
     * @var array<string, bool>
     */
    protected const SECTIONS = [
        'photos' => false,
        'albums' => true,
        'tags' => true,
        'moodboards' => true,
        'galeries' => true,
        'articles' => true,
        'commentaires' => false,
        'pages' => true,
        'livres' => true,
        'tirages' => true,
        'typetirages' => true,
        'expositions' => true,
        'videos' => true,
        'typevideos' => true,
        'modeles' => true,
        'shootings' => true,
        'messages' => false,
        'demandes' => true,
        'users' => true,
        'config' => true,
    ];

    /**
     * Toutes les entrées du menu du back-office, sans exception.
     *
     * Ce test est la raison d'être du fichier : le menu renvoie vers une
     * vingtaine de contrôleurs, et un lien vers une section absente ne se
     * manifeste qu'au clic, en 404 ou en 500.
     *
     * @return void
     */
    public function testToutesLesEntreesDuMenuRepondent(): void
    {
        $this->connecter($this->idAdmin);

        $this->get('/admin');
        $this->assertResponseOk('Le tableau de bord devrait répondre.');

        $this->get('/admin/photos/ajouter');
        $this->assertResponseOk("L'import de photos devrait répondre.");

        foreach (self::SECTIONS as $section => $creation) {
            $this->get('/admin/' . $section);
            $this->assertResponseOk(sprintf('/admin/%s devrait répondre.', $section));

            if (!$creation) {
                continue;
            }

            // Le formulaire de création est atteint sans identifiant : c'est le
            // chemin qui casse en premier quand une action `modifier` suppose un
            // enregistrement existant.
            $this->get('/admin/' . $section . '/modifier');
            $this->assertResponseOk(sprintf('/admin/%s/modifier devrait répondre.', $section));
        }
    }

    /**
     * Le menu lui-même : chaque lien qu'il affiche doit correspondre à une
     * section réellement servie. Vérifier les URL une à une ne dirait rien d'un
     * lien qui aurait été oublié dans le gabarit.
     *
     * @return void
     */
    public function testLeMenuNeRenvoieQueVersDesSectionsExistantes(): void
    {
        $this->connecter($this->idAdmin);
        $this->get('/admin');
        $this->assertResponseOk();

        preg_match_all('#href="/admin/([a-z-]+)"#', (string)$this->_response->getBody(), $trouves);

        $connues = array_merge(array_keys(self::SECTIONS), ['tableau']);
        $inconnues = array_diff(array_unique($trouves[1]), $connues);

        $this->assertSame([], array_values($inconnues), sprintf(
            'Le menu renvoie vers des sections non couvertes : %s',
            implode(', ', $inconnues),
        ));
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
     * Les formulaires d'album, de moodboard et de galerie doivent tous mener au
     * module de rattachement des photos : c'est le seul chemin vers lui.
     *
     * @return void
     */
    public function testLesFormulairesMenentAuModuleDeSelection(): void
    {
        $this->connecter($this->idAdmin);

        $cibles = [
            'Albums' => ['nom' => 'Album lien de test'],
            'Moodboards' => ['titre' => 'Moodboard lien de test', 'theme' => 'mosaique-flip', 'visibilite' => 'lien'],
            'Galeries' => ['nom' => 'Galerie lien de test'],
        ];
        $types = ['Albums' => 'album', 'Moodboards' => 'moodboard', 'Galeries' => 'galerie'];

        foreach ($cibles as $modele => $donnees) {
            $table = TableRegistry::getTableLocator()->get($modele);
            $entite = $table->newEntity($donnees);
            $table->saveOrFail($entite);

            $this->get(sprintf('/admin/%s/modifier/%d', strtolower($modele), $entite->id));
            $this->assertResponseOk();
            $this->assertResponseContains(
                sprintf('/admin/selection-photos/index/%s/%d', $types[$modele], $entite->id),
                sprintf('Le formulaire %s devrait mener au module de sélection.', $modele),
            );

            $table->delete($entite);
        }
    }

    /**
     * La fiche d'une photo.
     *
     * Elle a longtemps manqué : la liste des photos affichait un lien
     * « Modifier » vers une action qui existait mais dont le gabarit n'avait
     * jamais été écrit, ce qui donnait une erreur 500 au clic.
     *
     * @return void
     */
    public function testFicheDUnePhoto(): void
    {
        $this->connecter($this->idAdmin);

        $photos = TableRegistry::getTableLocator()->get('Photos');
        $photos->deleteAll(['fichier' => 'photo-de-test-back-office.jpg']);

        $photo = $photos->newEntity(['titre' => 'Photo de test back-office']);
        $photo->set('uuid', Text::uuid());
        $photo->set('fichier', 'photo-de-test-back-office.jpg');
        $photo->set('extension_origine', 'jpg');
        $photo->set('largeur', 1600);
        $photo->set('hauteur', 1067);
        $photos->saveOrFail($photo);

        $this->get('/admin/photos/modifier/' . $photo->id);

        $this->assertResponseOk('La fiche de la photo devrait répondre.');
        $this->assertResponseContains('Photo de test back-office');
        // Les deux opérations propres au pipeline d'images doivent être offertes :
        // sans elles, un dérivé manquant n'a aucun moyen d'être régénéré.
        $this->assertResponseContains('Régénérer les déclinaisons');
        $this->assertResponseContains('Supprimer la photo');

        $photos->delete($photo);
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
