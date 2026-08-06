<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Membre;

use App\Model\Enum\Role;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * L'espace membre, du point de vue du client connecté.
 *
 * C'est la zone la plus facile à croire terminée sans l'être : les écrans
 * s'affichent, mais les actions qu'ils proposent partent vers le chemin d'accès
 * par lien, qui n'a pas les mêmes conditions d'entrée. Ces tests exercent donc
 * les opérations, pas seulement l'affichage.
 */
class EspaceMembreTest extends TestCase
{
    use IntegrationTestTrait;

    protected const EMAIL_CLIENT = 'client-espace@test.local';

    protected const EMAIL_AUTRE = 'autre-espace@test.local';

    protected const MOT_DE_PASSE_GALERIE = 'mot-de-passe-galerie';

    /**
     * Nom de base du fichier, sans variante ni extension : c'est la convention
     * du pipeline d'images, que l'archive ZIP suit pour retrouver les dérivés.
     */
    protected const BASE_FICHIER = 'photo-espace-membre';

    protected int $idClient;

    protected int $idAutre;

    protected int $idGalerie;

    protected int $idPhoto;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll(['email IN' => [self::EMAIL_CLIENT, self::EMAIL_AUTRE]]);

        $this->idClient = $this->creerMembre(self::EMAIL_CLIENT);
        $this->idAutre = $this->creerMembre(self::EMAIL_AUTRE);

        $photos = TableRegistry::getTableLocator()->get('Photos');
        $photos->deleteAll(['fichier' => self::BASE_FICHIER]);

        $photo = $photos->newEntity(['titre' => 'Photo espace membre']);
        $photo->set('uuid', Text::uuid());
        $photo->set('fichier', self::BASE_FICHIER);
        $photo->set('extension_origine', 'jpg');
        $photo->set('largeur', 1600);
        $photo->set('hauteur', 1067);
        $photos->saveOrFail($photo);
        $this->idPhoto = $photo->id;

        $galeries = TableRegistry::getTableLocator()->get('Galeries');
        $galeries->deleteAll(['nom' => 'Livraison espace membre']);

        // Galerie protégée par mot de passe : c'est le cas courant d'une
        // livraison client, et celui où l'accès par lien et l'accès par compte
        // divergent.
        $galerie = $galeries->newEntity([
            'nom' => 'Livraison espace membre',
            'client_id' => $this->idClient,
            'telechargement_actif' => true,
            'password' => self::MOT_DE_PASSE_GALERIE,
        ]);
        $galerie->photos = [$photo];
        $galeries->saveOrFail($galerie);
        $this->idGalerie = $galerie->id;
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Galeries')->deleteAll(['nom' => 'Livraison espace membre']);
        TableRegistry::getTableLocator()->get('Photos')->deleteAll(['fichier' => self::BASE_FICHIER]);

        if (is_file($this->cheminDerive())) {
            unlink($this->cheminDerive());
        }
        TableRegistry::getTableLocator()->get('Users')
            ->deleteAll(['email IN' => [self::EMAIL_CLIENT, self::EMAIL_AUTRE]]);

        parent::tearDown();
    }

    /**
     * Chemin du dérivé `large`, celui que l'archive ZIP embarque.
     *
     * @return string
     */
    protected function cheminDerive(): string
    {
        return WWW_ROOT . 'media' . DS . 'photos' . DS . self::BASE_FICHIER . '-large.jpeg';
    }

    /**
     * Pose un dérivé réel sur le disque.
     *
     * Le test du téléchargement ne prouverait rien sans lui : le service refuse
     * de livrer une archive vide, et répondrait 404 pour une raison qui n'a rien
     * à voir avec les autorisations.
     *
     * @return void
     */
    protected function poserDerive(): void
    {
        $dossier = dirname($this->cheminDerive());

        if (!is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $image = imagecreatetruecolor(10, 10);
        imagejpeg($image, $this->cheminDerive());
        imagedestroy($image);
    }

    /**
     * @param string $email Adresse du compte à créer.
     * @return int
     */
    protected function creerMembre(string $email): int
    {
        $users = TableRegistry::getTableLocator()->get('Users');

        $membre = $users->newEntity([
            'email' => $email,
            'password' => 'mot-de-passe-de-test',
            'nom' => 'Client',
        ]);
        $membre->role = Role::Membre;
        $users->saveOrFail($membre);

        return $membre->id;
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
     * @return void
     */
    public function testTableauDeBordEtGalerieRepondent(): void
    {
        $this->connecter($this->idClient);

        $this->get('/membre');
        $this->assertResponseOk();
        $this->assertResponseContains('Livraison espace membre');

        $this->get('/membre/galeries/voir/' . $this->idGalerie);
        $this->assertResponseOk();
    }

    /**
     * Le cœur du proofing : retenir une photo depuis son compte.
     *
     * Le membre est passé par `GaleriePolicy`, il n'a aucune raison de connaître
     * le mot de passe du lien de partage — son compte *est* son autorisation.
     *
     * @return void
     */
    public function testUnMembrePeutRetenirUnePhotoDepuisSonCompte(): void
    {
        $this->connecter($this->idClient);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(sprintf('/membre/galeries/favori/%d/%d', $this->idGalerie, $this->idPhoto));

        $this->assertResponseSuccess('Retenir une photo depuis son compte devrait fonctionner.');

        $favoris = TableRegistry::getTableLocator()->get('GalerieFavoris');
        $this->assertNotNull(
            $favoris->find()->where([
                'galerie_id' => $this->idGalerie,
                'photo_id' => $this->idPhoto,
                'user_id' => $this->idClient,
            ])->first(),
            'Le favori aurait dû être enregistré.',
        );
    }

    /**
     * @return void
     */
    public function testUnMembrePeutTelechargerSaSelection(): void
    {
        $this->poserDerive();
        $this->connecter($this->idClient);

        $this->get('/membre/galeries/telecharger/' . $this->idGalerie);

        $this->assertResponseSuccess('Le téléchargement depuis le compte devrait fonctionner.');
        $this->assertHeaderContains('Content-Type', 'application/zip');
    }

    /**
     * Sans dérivé sur le disque, mieux vaut refuser que livrer une archive vide
     * que le client croirait complète.
     *
     * @return void
     */
    public function testUneArchiveSansFichierEstRefusee(): void
    {
        $this->connecter($this->idClient);

        $this->get('/membre/galeries/telecharger/' . $this->idGalerie);

        $this->assertResponseCode(404);
    }

    /**
     * Les retours sur une livraison : la table `galerie_commentaires` existait
     * dès le premier schéma sans que rien ne l'alimente.
     *
     * @return void
     */
    public function testUnMembrePeutDeposerUnRetourSurSaLivraison(): void
    {
        $this->connecter($this->idClient);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/membre/galeries/commenter/' . $this->idGalerie, [
            'contenu' => 'La troisième photo est un peu sombre.',
        ]);
        $this->assertRedirect('/membre/galeries/voir/' . $this->idGalerie);

        $retour = TableRegistry::getTableLocator()->get('GalerieCommentaires')->find()
            ->where(['galerie_id' => $this->idGalerie])
            ->first();

        $this->assertNotNull($retour, 'Le retour aurait dû être enregistré.');
        $this->assertSame($this->idClient, $retour->user_id, 'Le retour doit être attribué à son auteur.');

        $this->get('/membre/galeries/voir/' . $this->idGalerie);
        $this->assertResponseContains('La troisième photo est un peu sombre.');
    }

    /**
     * Un moodboard privé adressé au membre doit s'ouvrir depuis son compte, sans
     * repasser par le mot de passe du lien de partage.
     *
     * @return void
     */
    public function testUnMembreOuvreSonMoodboardDepuisSonCompte(): void
    {
        $moodboards = TableRegistry::getTableLocator()->get('Moodboards');
        $moodboards->deleteAll(['titre' => 'Moodboard espace membre']);

        $moodboard = $moodboards->newEntity([
            'titre' => 'Moodboard espace membre',
            'visibilite' => 'prive',
            'theme' => 'mosaique-flip',
            'destinataire_id' => $this->idClient,
            'commentaires_actifs' => true,
            'password' => 'secret-moodboard',
        ]);
        $moodboards->saveOrFail($moodboard);

        $this->connecter($this->idClient);
        $this->get('/membre/moodboards/voir/' . $moodboard->id);
        $this->assertResponseOk('Le destinataire devrait accéder à son moodboard.');
        $this->assertResponseContains('Moodboard espace membre');

        // Un autre membre n'est pas destinataire : refus.
        $this->connecter($this->idAutre);
        $this->get('/membre/moodboards/voir/' . $moodboard->id);
        $this->assertResponseCode(403);

        $moodboards->deleteAll(['titre' => 'Moodboard espace membre']);
    }

    /**
     * La fiche du compte, et le changement de mot de passe qui redemande
     * l'ancien.
     *
     * @return void
     */
    public function testFicheDuCompteEtChangementDeMotDePasse(): void
    {
        $this->connecter($this->idClient);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->get('/membre/compte');
        $this->assertResponseOk();
        $this->assertResponseContains(self::EMAIL_CLIENT);

        $this->post('/membre/compte', ['prenom' => 'Camille', 'telephone' => '0600000000']);
        $this->assertRedirect('/membre/compte');

        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertSame('Camille', $users->get($this->idClient)->prenom);

        // Mauvais mot de passe actuel : rien ne change.
        $empreinte = $users->get($this->idClient)->password;
        $this->post('/membre/compte/mot-de-passe', [
            'mot_de_passe_actuel' => 'ce-n-est-pas-le-bon',
            'password' => 'nouveau-mot-de-passe-long',
        ]);
        $this->assertSame($empreinte, $users->get($this->idClient)->password);

        // Bon mot de passe actuel : le changement passe.
        $this->post('/membre/compte/mot-de-passe', [
            'mot_de_passe_actuel' => 'mot-de-passe-de-test',
            'password' => 'nouveau-mot-de-passe-long',
        ]);
        $this->assertRedirect('/membre/compte');

        $apres = $users->get($this->idClient);
        $this->assertNotSame($empreinte, $apres->password, 'Le mot de passe aurait dû changer.');
        $this->assertTrue($apres->verifierMotDePasse('nouveau-mot-de-passe-long'));
    }

    /**
     * Un membre ne peut pas se promouvoir en modifiant sa fiche : `role` et
     * `actif` ne sont pas des champs de ce formulaire.
     *
     * @return void
     */
    public function testUnMembreNePeutPasSePromouvoirDepuisSaFiche(): void
    {
        $this->connecter($this->idClient);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/membre/compte', [
            'prenom' => 'Camille',
            'role' => 'admin',
            'actif' => '0',
            'email' => 'usurpation@test.local',
        ]);

        $apres = TableRegistry::getTableLocator()->get('Users')->get($this->idClient);
        $this->assertSame(Role::Membre, $apres->role, 'Le rôle ne devait pas changer.');
        $this->assertTrue($apres->actif, 'Le compte ne devait pas être désactivé.');
        $this->assertSame(self::EMAIL_CLIENT, $apres->email, "L'adresse ne devait pas changer.");
    }

    /**
     * Un membre ne doit atteindre ni la galerie d'un autre, ni ses opérations.
     *
     * @return void
     */
    public function testUnAutreMembreNAccedePasALaGalerie(): void
    {
        $this->connecter($this->idAutre);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->get('/membre/galeries/voir/' . $this->idGalerie);
        $this->assertResponseCode(403);

        $this->post(sprintf('/membre/galeries/favori/%d/%d', $this->idGalerie, $this->idPhoto));
        $this->assertResponseCode(403);

        $this->get('/membre/galeries/telecharger/' . $this->idGalerie);
        $this->assertResponseCode(403);
    }
}
