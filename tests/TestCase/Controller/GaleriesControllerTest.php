<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Galeries client, par lien de partage et depuis l'espace membre.
 *
 * Le scénario le plus important est le dernier : deux clients distincts, chacun
 * avec sa livraison. Rien ne doit permettre à l'un de voir celle de l'autre.
 */
class GaleriesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected int $clientA;

    protected int $clientB;

    protected int $galerieA;

    protected string $tokenA = 'jeton-galerie-test-aaaaaaaaaa';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $users = TableRegistry::getTableLocator()->get('Users');
        $galeries = TableRegistry::getTableLocator()->get('Galeries');

        $galeries->deleteAll(['share_token' => $this->tokenA]);
        $users->deleteAll(['email IN' => ['clienta@test.local', 'clientb@test.local']]);

        $a = $users->newEntity([
            'email' => 'clienta@test.local',
            'password' => 'mot-de-passe-client-a',
            'prenom' => 'Client',
            'nom' => 'A',
        ]);
        $users->saveOrFail($a);
        $this->clientA = $a->id;

        $b = $users->newEntity([
            'email' => 'clientb@test.local',
            'password' => 'mot-de-passe-client-b',
            'prenom' => 'Client',
            'nom' => 'B',
        ]);
        $users->saveOrFail($b);
        $this->clientB = $b->id;

        $galerie = $galeries->newEntity([
            'nom' => 'Livraison du client A',
            'client_id' => $this->clientA,
            'actif' => true,
            'telechargement_actif' => true,
        ]);
        $galerie->share_token = $this->tokenA;
        $galeries->saveOrFail($galerie);
        $this->galerieA = $galerie->id;
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Galeries')->deleteAll(['share_token' => $this->tokenA]);
        TableRegistry::getTableLocator()->get('Users')
            ->deleteAll(['email IN' => ['clienta@test.local', 'clientb@test.local']]);

        parent::tearDown();
    }

    /**
     * @param int $id Identifiant du compte à simuler.
     * @return void
     */
    protected function connecter(int $id): void
    {
        $utilisateur = TableRegistry::getTableLocator()->get('Users')->get($id);

        $this->session(['Auth' => $utilisateur]);
    }

    /**
     * @return void
     */
    public function testLienDePartageSansMotDePasseDonneAcces(): void
    {
        $this->get('/g/' . $this->tokenA);

        $this->assertResponseOk();
        $this->assertResponseContains('Livraison du client A');
    }

    /**
     * @return void
     */
    public function testJetonInconnuRenvoieUne404(): void
    {
        $this->get('/g/jeton-inexistant');

        $this->assertResponseCode(404);
    }

    /**
     * @return void
     */
    public function testClientDestinataireVoitSaGalerie(): void
    {
        $this->connecter($this->clientA);

        $this->get('/membre/galeries/voir/' . $this->galerieA);

        $this->assertResponseOk();
        $this->assertResponseContains('Livraison du client A');
    }

    /**
     * Le test qui compte : un autre membre, connecté et légitime par ailleurs,
     * ne doit rien voir de cette livraison.
     *
     * @return void
     */
    public function testAutreMembreNeVoitPasLaGalerie(): void
    {
        $this->connecter($this->clientB);

        $this->get('/membre/galeries/voir/' . $this->galerieA);

        // On assert le code plutôt que l'absence du titre dans le corps : en
        // mode debug, la page d'erreur affiche le contexte de l'exception, ce
        // qui n'est pas le cas en production où `debug` vaut false.
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testVisiteurAnonymeEstRedirigeVersLaConnexion(): void
    {
        $this->get('/membre/galeries/voir/' . $this->galerieA);

        $this->assertRedirectContains('/connexion');
    }

    /**
     * Une galerie expirée cesse d'être consultable par le client, même connecté.
     *
     * @return void
     */
    public function testGalerieExpireeEstRefusee(): void
    {
        $galeries = TableRegistry::getTableLocator()->get('Galeries');
        $galerie = $galeries->get($this->galerieA);
        $galerie->expires_at = new DateTime('-1 day');
        $galeries->saveOrFail($galerie);

        $this->connecter($this->clientA);
        $this->get('/membre/galeries/voir/' . $this->galerieA);

        $this->assertResponseError();
    }
}
