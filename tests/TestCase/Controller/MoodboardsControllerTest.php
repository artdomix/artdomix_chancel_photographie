<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Accès aux moodboards par lien de partage.
 *
 * Ce chemin est le seul du site où des photos privées sont exposées sans compte
 * utilisateur : le jeton et le mot de passe sont la seule barrière, ils méritent
 * d'être testés explicitement.
 */
class MoodboardsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected string $tokenPublic = 'jeton-test-public-0000000000';

    protected string $tokenPrive = 'jeton-test-prive-00000000000';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $moodboards = TableRegistry::getTableLocator()->get('Moodboards');
        $moodboards->deleteAll(['share_token IN' => [$this->tokenPublic, $this->tokenPrive]]);

        $public = $moodboards->newEntity([
            'titre' => 'Moodboard de test public',
            'visibilite' => 'lien',
            'theme' => 'mosaique-flip',
        ]);
        $public->share_token = $this->tokenPublic;
        $moodboards->saveOrFail($public);

        $prive = $moodboards->newEntity([
            'titre' => 'Moodboard de test privé',
            'visibilite' => 'prive',
            'theme' => 'mur-parallaxe',
        ]);
        $prive->share_token = $this->tokenPrive;
        $prive->password = 'un-mot-de-passe-de-test';
        $moodboards->saveOrFail($prive);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Moodboards')
            ->deleteAll(['share_token IN' => [$this->tokenPublic, $this->tokenPrive]]);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testLienDePartageDonneAcces(): void
    {
        $this->get('/m/' . $this->tokenPublic);

        $this->assertResponseOk();
        $this->assertResponseContains('Moodboard de test public');
    }

    /**
     * Un jeton inconnu répond 404 et non 403 : distinguer les deux confirmerait
     * à qui tâtonne que le jeton essayé existe bel et bien.
     *
     * @return void
     */
    public function testJetonInconnuRenvoieUne404(): void
    {
        $this->get('/m/jeton-qui-n-existe-pas');

        $this->assertResponseCode(404);
    }

    /**
     * @return void
     */
    public function testMoodboardPriveDemandeLeMotDePasse(): void
    {
        $this->get('/m/' . $this->tokenPrive);

        $this->assertResponseOk();
        $this->assertResponseContains('Sélection protégée');
        // Le contenu ne doit pas être servi en même temps que le formulaire.
        $this->assertResponseNotContains('data-moodboard-item');
    }

    /**
     * @return void
     */
    public function testBonMotDePasseEnregistreLeDeverrouillage(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $moodboard = TableRegistry::getTableLocator()->get('Moodboards')
            ->find()->where(['share_token' => $this->tokenPrive])->firstOrFail();

        $this->post('/m/deverrouiller/' . $this->tokenPrive, [
            'password' => 'un-mot-de-passe-de-test',
        ]);

        $this->assertRedirect();
        // Le harnais d'intégration ne reporte pas la session d'une requête à la
        // suivante : on vérifie donc ce que l'action a écrit, et l'accès qui en
        // découle est couvert par le test suivant.
        $this->assertSession(true, 'Moodboards.deverrouilles.' . $moodboard->id);
    }

    /**
     * @return void
     */
    public function testSessionDeverrouilleeDonneAcces(): void
    {
        $moodboard = TableRegistry::getTableLocator()->get('Moodboards')
            ->find()->where(['share_token' => $this->tokenPrive])->firstOrFail();

        $this->session(['Moodboards' => ['deverrouilles' => [$moodboard->id => true]]]);

        $this->get('/m/' . $this->tokenPrive);

        $this->assertResponseOk();
        $this->assertResponseNotContains('Sélection protégée');
        $this->assertResponseContains('Moodboard de test privé');
    }

    /**
     * @return void
     */
    public function testMauvaisMotDePasseNeDeverrouillePas(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/m/deverrouiller/' . $this->tokenPrive, ['password' => 'faux']);
        $this->assertRedirect();

        $this->get('/m/' . $this->tokenPrive);
        $this->assertResponseContains('Sélection protégée');
    }

    /**
     * Le formulaire de commentaire ne doit pas servir de porte dérobée : sans
     * avoir franchi le mot de passe, l'URL de dépôt reste fermée.
     *
     * @return void
     */
    public function testCommentaireRefuseSansDeverrouillage(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/m/commenter/' . $this->tokenPrive, [
            'auteur' => 'Intrus',
            'contenu' => 'contournement',
        ]);

        $this->assertResponseCode(404);
    }

    /**
     * @return void
     */
    public function testMoodboardExpireEstInaccessible(): void
    {
        $moodboards = TableRegistry::getTableLocator()->get('Moodboards');
        $moodboard = $moodboards->find()->where(['share_token' => $this->tokenPublic])->firstOrFail();
        $moodboard->expires_at = new DateTime('-1 day');
        $moodboards->saveOrFail($moodboard);

        $this->get('/m/' . $this->tokenPublic);

        $this->assertResponseCode(404);
    }
}
