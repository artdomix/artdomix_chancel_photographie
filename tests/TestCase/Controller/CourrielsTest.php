<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\Role;
use Cake\Mailer\TransportFactory;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Les courriels partants du site public.
 *
 * Ils étaient jusqu'ici construits en texte brut dans les contrôleurs, sans
 * gabarit ni test : rien ne disait si le lien de réinitialisation était bien
 * absolu, ni si le visiteur recevait quoi que ce soit après son message.
 */
class CourrielsTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    protected const EMAIL_MEMBRE = 'courriel@test.local';

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Users')->deleteAll(['email' => self::EMAIL_MEMBRE]);
        TableRegistry::getTableLocator()->get('Messages')->deleteAll(['email' => 'visiteur@test.local']);

        parent::tearDown();
    }

    /**
     * Le formulaire de contact envoie deux courriels : la notification au
     * photographe, et l'accusé de réception au visiteur — qui manquait.
     *
     * @return void
     */
    public function testLeContactPrevientLePhotographeEtLeVisiteur(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        TableRegistry::getTableLocator()->get('Messages')->deleteAll(['email' => 'visiteur@test.local']);

        // Connecté : le contrôleur ne monte le captcha que pour un visiteur
        // anonyme, ce qui permet d'exercer l'envoi par la vraie route HTTP sans
        // reproduire l'épreuve. Les courriels partants sont les mêmes.
        $this->connecterUnMembre();

        $this->post('/contact', [
            'nom' => 'Visiteur de test',
            'email' => 'visiteur@test.local',
            'sujet' => 'Demande de devis',
            'contenu' => "Bonjour,\nJe cherche un photographe pour un mariage.",
        ]);

        $this->assertRedirect('/contact');

        $this->assertMailCount(2, 'Deux courriels devraient partir : notification et accusé.');

        // Notification au photographe : contenu du message, et réponse dirigée
        // vers le visiteur.
        $this->assertMailContainsAt(0, 'Je cherche un photographe');
        $this->assertMailSentWithAt(0, 'visiteur@test.local', 'replyTo');

        // Accusé de réception au visiteur : il manquait entièrement.
        $this->assertMailSentToAt(1, 'visiteur@test.local');
        $this->assertMailContainsAt(1, 'Visiteur de test');
    }

    /**
     * @return void
     */
    protected function connecterUnMembre(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email' => self::EMAIL_MEMBRE]);

        $membre = $users->newEntity([
            'email' => self::EMAIL_MEMBRE,
            'password' => 'mot-de-passe-de-test',
            'nom' => 'Membre',
        ]);
        $membre->role = Role::Membre;
        $users->saveOrFail($membre);

        $this->session(['Auth' => $users->get($membre->id)]);
    }

    /**
     * @return void
     */
    public function testLeLienDeReinitialisationEstAbsoluEtPresent(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email' => self::EMAIL_MEMBRE]);
        $membre = $users->newEntity([
            'email' => self::EMAIL_MEMBRE,
            'password' => 'mot-de-passe-de-test',
            'nom' => 'Membre',
        ]);
        $membre->role = Role::Membre;
        $users->saveOrFail($membre);

        $this->post('/mot-de-passe-oublie', ['email' => self::EMAIL_MEMBRE]);
        $this->assertRedirect('/connexion');

        $this->assertMailCount(1);
        $this->assertMailSentTo(self::EMAIL_MEMBRE);

        $jeton = $users->get($membre->id)->token;
        $this->assertNotEmpty($jeton, 'Un jeton devrait avoir été posé.');
        // URL absolue : un chemin relatif dans un courriel ne mène nulle part.
        $this->assertMailContains('http');
        $this->assertMailContains('/reinitialiser/' . $jeton);
    }

    /**
     * Non-régression : un SMTP en panne ne doit pas trahir l'existence d'un
     * compte. Avant correction, l'adresse connue produisait une 500 quand
     * l'adresse inconnue redirigeait — de quoi énumérer les comptes du site.
     *
     * @return void
     */
    public function testUnEnvoiEnEchecNeTrahitPasLExistenceDUnCompte(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email' => self::EMAIL_MEMBRE]);
        $membre = $users->newEntity([
            'email' => self::EMAIL_MEMBRE,
            'password' => 'mot-de-passe-de-test',
            'nom' => 'Membre',
        ]);
        $membre->role = Role::Membre;
        $users->saveOrFail($membre);

        // Transport injoignable : l'état d'un hébergement dont le SMTP n'est pas
        // encore réglé, c'est-à-dire celui de toute installation neuve.
        $ancien = TransportFactory::getConfig('default');
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', [
            'className' => 'Smtp',
            'host' => '127.0.0.1',
            'port' => 1,
            'timeout' => 1,
        ]);

        try {
            $this->enableCsrfToken();
            $this->enableSecurityToken();

            $this->post('/mot-de-passe-oublie', ['email' => 'inconnue-ici@test.local']);
            $inconnue = $this->_response->getStatusCode();

            $this->post('/mot-de-passe-oublie', ['email' => self::EMAIL_MEMBRE]);
            $connue = $this->_response->getStatusCode();
        } finally {
            TransportFactory::drop('default');
            TransportFactory::setConfig('default', $ancien);
        }

        $this->assertSame($inconnue, $connue, sprintf(
            'Les deux réponses doivent être identiques (inconnue : %d, connue : %d).',
            $inconnue,
            $connue,
        ));
    }
}
