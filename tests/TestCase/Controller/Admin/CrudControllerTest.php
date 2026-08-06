<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Controller\Admin\CrudController;
use App\Model\Enum\Role;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;

/**
 * Les sections d'administration bâties sur `CrudController`.
 *
 * Une section décrit ses champs par leur nom, et le FormHelper accepte sans
 * broncher un nom qui ne correspond à aucune colonne : le champ s'affiche, se
 * remplit, et son contenu disparaît à l'enregistrement. Un simple appel GET ne
 * détecterait rien. D'où deux vérifications : la cohérence de chaque
 * déclaration avec le schéma, et un aller-retour d'écriture réel.
 */
class CrudControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected const EMAIL_ADMIN = 'admin-crud@test.local';

    /**
     * Champs de formulaire qui n'ont volontairement pas de colonne : ce sont des
     * propriétés virtuelles portées par l'entité.
     *
     * @var array<string>
     */
    protected const CHAMPS_VIRTUELS = ['password'];

    protected int $idAdmin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email' => self::EMAIL_ADMIN]);

        $admin = $users->newEntity([
            'email' => self::EMAIL_ADMIN,
            'password' => 'mot-de-passe-de-test-1',
            'nom' => 'Admin',
        ]);
        $admin->role = Role::Admin;
        $users->saveOrFail($admin);
        $this->idAdmin = $admin->id;

        $this->session(['Auth' => $admin]);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Users')->deleteAll(['email' => self::EMAIL_ADMIN]);

        parent::tearDown();
    }

    /**
     * Chaque contrôleur bâti sur le socle, avec le nom de sa table.
     *
     * @return array<string, array{0: class-string}>
     */
    public static function sectionsCrud(): array
    {
        $cas = [];

        foreach (glob(APP . 'Controller/Admin/*Controller.php') ?: [] as $fichier) {
            $classe = 'App\\Controller\\Admin\\' . basename($fichier, '.php');
            $reflet = new ReflectionClass($classe);

            if ($reflet->isAbstract() || !$reflet->isSubclassOf(CrudController::class)) {
                continue;
            }

            $cas[$reflet->getShortName()] = [$classe];
        }

        return $cas;
    }

    /**
     * Colonnes de liste et champs de formulaire doivent exister réellement.
     *
     * @param class-string $classe Contrôleur à examiner.
     * @return void
     */
    #[DataProvider('sectionsCrud')]
    public function testLesChampsDeclaresExistentDansLeSchema(string $classe): void
    {
        $controleur = new $classe(new ServerRequest());
        $reflet = new ReflectionClass($classe);

        $modele = $this->lirePropriete($reflet, $controleur, 'modele');
        $table = TableRegistry::getTableLocator()->get($modele);
        $schema = $table->getSchema();

        $methode = new ReflectionMethod($classe, 'champs');
        $methode->setAccessible(true);
        $champs = $methode->invoke($controleur, $table->newEmptyEntity());

        foreach (array_keys($champs) as $champ) {
            if (in_array($champ, self::CHAMPS_VIRTUELS, true)) {
                continue;
            }

            $this->assertTrue($schema->hasColumn($champ), sprintf(
                '%s déclare le champ « %s », absent de la table %s.',
                $reflet->getShortName(),
                $champ,
                $modele,
            ));
        }

        // Les colonnes de liste acceptent en plus une association, que
        // l'AdminHelper sait rendre par son libellé. La comparaison porte sur le
        // *nom de propriété* de l'association (`photo`) et non sur son alias
        // (`Photos`) : c'est la propriété que l'entité expose, donc la seule que
        // `Hash::get` sait atteindre.
        foreach (array_keys($this->lirePropriete($reflet, $controleur, 'colonnes')) as $colonne) {
            $racine = explode('.', $colonne)[0];
            $existe = $schema->hasColumn($racine)
                || $table->associations()->getByProperty($racine) !== null;

            $this->assertTrue($existe, sprintf(
                '%s liste la colonne « %s », qui n\'est ni un champ ni une association de %s.',
                $reflet->getShortName(),
                $colonne,
                $modele,
            ));
        }
    }

    /**
     * Lit une propriété protégée de configuration d'une section.
     *
     * @param \ReflectionClass<object> $reflet Réflexion sur le contrôleur.
     * @param object $controleur Instance examinée.
     * @param string $nom Nom de la propriété.
     * @return mixed
     */
    protected function lirePropriete(ReflectionClass $reflet, object $controleur, string $nom): mixed
    {
        $propriete = $reflet->getProperty($nom);
        $propriete->setAccessible(true);

        return $propriete->getValue($controleur);
    }

    /**
     * Un aller-retour complet sur une section simple : le formulaire écrit
     * vraiment, la liste montre le résultat, la suppression le retire.
     *
     * @return void
     */
    public function testCycleCompletSurUneSection(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $tags = TableRegistry::getTableLocator()->get('Tags');
        $tags->deleteAll(['nom' => 'Tag de test CRUD']);

        $this->post('/admin/tags/modifier', [
            'nom' => 'Tag de test CRUD',
            'type' => 'lieu',
            'description' => 'Créé par le test.',
        ]);
        $this->assertRedirect('/admin/tags');

        $tag = $tags->find()->where(['nom' => 'Tag de test CRUD'])->first();
        $this->assertNotNull($tag, 'Le formulaire aurait dû créer le tag.');
        $this->assertSame('lieu', $tag->type->value, 'La famille choisie devrait être enregistrée.');
        $this->assertNotEmpty($tag->slug, 'Le slug devrait être posé par le behavior.');

        $this->post("/admin/tags/modifier/{$tag->id}", [
            'nom' => 'Tag de test CRUD',
            'type' => 'materiel',
            'description' => 'Modifié par le test.',
        ]);
        $this->assertRedirect('/admin/tags');
        $this->assertSame('materiel', $tags->get($tag->id)->type->value);

        $this->post("/admin/tags/supprimer/{$tag->id}");
        $this->assertRedirect('/admin/tags');
        $this->assertNull($tags->find()->where(['id' => $tag->id])->first());
    }

    /**
     * La bascule de publication agit bien sur la colonne déclarée par la section
     * — `valide` pour les commentaires, `actif` ailleurs.
     *
     * @return void
     */
    public function testBasculeDePublication(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $pages = TableRegistry::getTableLocator()->get('Pages');
        $pages->deleteAll(['titre' => 'Page de test CRUD']);

        $page = $pages->newEntity(['titre' => 'Page de test CRUD', 'contenu' => 'Texte.', 'actif' => true]);
        $pages->saveOrFail($page);

        $this->post("/admin/pages/basculer/{$page->id}");
        $this->assertRedirect('/admin/pages');
        $this->assertFalse($pages->get($page->id)->actif, 'La page aurait dû être retirée du site.');

        $this->post("/admin/pages/basculer/{$page->id}");
        $this->assertTrue($pages->get($page->id)->actif, 'La page aurait dû revenir en ligne.');

        $pages->deleteAll(['titre' => 'Page de test CRUD']);
    }

    /**
     * Le rôle n'est pas assignable en masse : il ne doit changer que par le
     * chemin prévu, et jamais pour le compte de celui qui édite.
     *
     * @return void
     */
    public function testUnAdministrateurNePeutPasSeRetrograder(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $users = TableRegistry::getTableLocator()->get('Users');

        $this->post("/admin/users/modifier/{$this->idAdmin}", [
            'email' => self::EMAIL_ADMIN,
            'nom' => 'Admin',
            'role' => 'member',
            'password' => '',
            'actif' => '0',
        ]);

        $apres = $users->get($this->idAdmin);
        $this->assertSame(Role::Admin, $apres->role, 'Le rôle ne devait pas changer.');
        $this->assertTrue($apres->actif, 'Le compte ne devait pas être désactivé.');
    }

    /**
     * Un mot de passe laissé vide signifie « ne pas changer ». Sans ce
     * traitement, chaque enregistrement d'une fiche compte écraserait le mot de
     * passe et bloquerait le membre hors du site.
     *
     * @return void
     */
    public function testMotDePasseVideConserveLAncien(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email' => 'membre-crud@test.local']);

        $membre = $users->newEntity([
            'email' => 'membre-crud@test.local',
            'password' => 'mot-de-passe-initial',
            'nom' => 'Membre',
        ]);
        $membre->role = Role::Membre;
        $users->saveOrFail($membre);

        $empreinte = $users->get($membre->id)->password;

        $this->post("/admin/users/modifier/{$membre->id}", [
            'email' => 'membre-crud@test.local',
            'nom' => 'Membre renommé',
            'role' => 'member',
            'password' => '',
        ]);

        $apres = $users->get($membre->id);
        $this->assertSame($empreinte, $apres->password, "Le mot de passe n'aurait pas dû changer.");
        $this->assertSame('Membre renommé', $apres->nom, 'Le reste de la fiche aurait dû être enregistré.');

        $users->deleteAll(['email' => 'membre-crud@test.local']);
    }
}
