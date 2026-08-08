<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Enum\Role;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Le module de rattachement des photos.
 *
 * Avant lui, aucun écran ne permettait de mettre une photo dans un moodboard ou
 * dans une galerie : les contrôleurs acceptaient bien `Photos._ids`, mais le
 * formulaire qui aurait dû l'alimenter n'existait pas. Ces tests exercent
 * l'écriture réelle en base, pas seulement l'affichage.
 */
class SelectionPhotosTest extends TestCase
{
    use IntegrationTestTrait;

    protected const EMAIL_ADMIN = 'admin-selection@test.local';

    protected const BASE_FICHIER = 'photo-selection-';

    protected int $idAdmin;

    /**
     * @var array<int>
     */
    protected array $photos = [];

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
            'password' => 'mot-de-passe-de-test',
            'nom' => 'Admin',
        ]);
        $admin->role = Role::Admin;
        $users->saveOrFail($admin);
        $this->idAdmin = $admin->id;

        $this->session(['Auth' => $users->get($admin->id)]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $photos = TableRegistry::getTableLocator()->get('Photos');
        $photos->deleteAll(['fichier LIKE' => self::BASE_FICHIER . '%']);

        foreach (range(1, 3) as $i) {
            $photo = $photos->newEntity(['titre' => 'Photo de sélection ' . $i]);
            $photo->set('uuid', Text::uuid());
            $photo->set('fichier', self::BASE_FICHIER . $i);
            $photo->set('extension_origine', 'jpg');
            $photo->set('largeur', 1600);
            $photo->set('hauteur', 1067);
            $photos->saveOrFail($photo);
            $this->photos[] = $photo->id;
        }
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Albums')->deleteAll(['nom' => 'Album de sélection']);
        TableRegistry::getTableLocator()->get('Moodboards')->deleteAll(['titre' => 'Moodboard de sélection']);
        TableRegistry::getTableLocator()->get('Galeries')->deleteAll(['nom' => 'Galerie de sélection']);
        TableRegistry::getTableLocator()->get('Photos')
            ->deleteAll(['fichier LIKE' => self::BASE_FICHIER . '%']);
        TableRegistry::getTableLocator()->get('Users')->deleteAll(['email' => self::EMAIL_ADMIN]);

        parent::tearDown();
    }

    /**
     * Crée l'entité porteuse correspondant à un type de liaison.
     *
     * @param string $type Type de liaison.
     * @return int
     */
    protected function creerCible(string $type): int
    {
        $entite = match ($type) {
            'album' => TableRegistry::getTableLocator()->get('Albums')
                ->newEntity(['nom' => 'Album de sélection']),
            'moodboard' => TableRegistry::getTableLocator()->get('Moodboards')
                ->newEntity(['titre' => 'Moodboard de sélection', 'theme' => 'mosaique-flip', 'visibilite' => 'lien']),
            'galerie' => TableRegistry::getTableLocator()->get('Galeries')
                ->newEntity(['nom' => 'Galerie de sélection']),
        };

        $table = match ($type) {
            'album' => 'Albums',
            'moodboard' => 'Moodboards',
            'galerie' => 'Galeries',
        };

        TableRegistry::getTableLocator()->get($table)->saveOrFail($entite);

        return $entite->id;
    }

    /**
     * @param string $type Type de liaison.
     * @return string
     */
    protected function jonction(string $type): string
    {
        return match ($type) {
            'album' => 'AlbumsPhotos',
            'moodboard' => 'MoodboardsPhotos',
            'galerie' => 'GaleriesPhotos',
        };
    }

    /**
     * @param string $type Type de liaison.
     * @return string
     */
    protected function cleEtrangere(string $type): string
    {
        return $type === 'album' ? 'album_id' : $type . '_id';
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function typesDeLiaison(): array
    {
        return ['album' => ['album'], 'moodboard' => ['moodboard'], 'galerie' => ['galerie']];
    }

    /**
     * Le cycle complet, identique pour les trois liaisons : ouvrir, rattacher,
     * réordonner, détacher.
     *
     * @param string $type Type de liaison.
     * @return void
     */
    #[DataProvider('typesDeLiaison')]
    public function testCycleDeRattachement(string $type): void
    {
        $cibleId = $this->creerCible($type);
        $jonction = TableRegistry::getTableLocator()->get($this->jonction($type));
        $cle = $this->cleEtrangere($type);

        $this->get(sprintf('/admin/selection-photos/index/%s/%d', $type, $cibleId));
        $this->assertResponseOk(sprintf("L'écran de sélection d'un %s devrait répondre.", $type));
        $this->assertResponseContains('Photothèque');

        // Rattachement de deux photos, une par requête comme le fait htmx.
        foreach ([$this->photos[0], $this->photos[1]] as $photoId) {
            $this->post(sprintf('/admin/selection-photos/basculer/%s/%d/%d', $type, $cibleId, $photoId));
            $this->assertResponseSuccess();
        }

        $liees = $jonction->find()->where([$cle => $cibleId])->orderBy(['ordre' => 'ASC'])->all()
            ->extract('photo_id')->toList();
        $this->assertSame([$this->photos[0], $this->photos[1]], $liees, 'Les deux photos devraient être rattachées.');

        // Deuxième clic sur la même photo : elle est retirée.
        $this->post(sprintf('/admin/selection-photos/basculer/%s/%d/%d', $type, $cibleId, $this->photos[0]));
        $this->assertSame(1, $jonction->find()->where([$cle => $cibleId])->count());

        // Réordonnancement : la photo 3 est ajoutée puis passée en premier.
        $this->post(sprintf('/admin/selection-photos/basculer/%s/%d/%d', $type, $cibleId, $this->photos[2]));
        $this->post(
            sprintf('/admin/selection-photos/ordonner/%s/%d', $type, $cibleId),
            ['ordre' => [$this->photos[2], $this->photos[1]]],
        );

        $ordonnees = $jonction->find()->where([$cle => $cibleId])->orderBy(['ordre' => 'ASC'])->all()
            ->extract('photo_id')->toList();
        $this->assertSame([$this->photos[2], $this->photos[1]], $ordonnees, "L'ordre devrait être enregistré.");

        // Retrait ciblé, puis vidage complet.
        $this->post(
            sprintf('/admin/selection-photos/retirer/%s/%d', $type, $cibleId),
            ['ids' => [$this->photos[1]]],
        );
        $this->assertSame(1, $jonction->find()->where([$cle => $cibleId])->count());

        $this->post(sprintf('/admin/selection-photos/retirer/%s/%d', $type, $cibleId));
        $this->assertSame(0, $jonction->find()->where([$cle => $cibleId])->count());
    }

    /**
     * Une photo déjà rattachée ne doit pas l'être deux fois : l'index unique
     * ferait échouer l'enregistrement du lot entier.
     *
     * @return void
     */
    public function testUnRattachementEnMasseIgnoreLesDoublons(): void
    {
        $cibleId = $this->creerCible('album');
        $jonction = TableRegistry::getTableLocator()->get('AlbumsPhotos');

        $this->post('/admin/selection-photos/basculer/album/' . $cibleId . '/' . $this->photos[0]);

        $this->post('/admin/photos/en-masse', [
            'action_masse' => 'rattacher',
            'cible' => 'album:' . $cibleId,
            'ids' => $this->photos,
        ]);
        $this->assertRedirect('/admin/photos');

        $this->assertSame(
            3,
            $jonction->find()->where(['album_id' => $cibleId])->count(),
            'Les trois photos devraient être présentes, sans doublon.',
        );

        // Rejouer la même action ne doit rien ajouter ni rien casser.
        $this->post('/admin/photos/en-masse', [
            'action_masse' => 'rattacher',
            'cible' => 'album:' . $cibleId,
            'ids' => $this->photos,
        ]);
        $this->assertSame(3, $jonction->find()->where(['album_id' => $cibleId])->count());
    }

    /**
     * La note et la mise en avant d'un moodboard, lues par le gabarit public
     * mais jusqu'ici impossibles à saisir.
     *
     * @return void
     */
    public function testNoteEtMiseEnAvantDUnMoodboard(): void
    {
        $cibleId = $this->creerCible('moodboard');
        $this->post('/admin/selection-photos/basculer/moodboard/' . $cibleId . '/' . $this->photos[0]);

        $this->post(
            sprintf('/admin/selection-photos/annoter/moodboard/%d/%d', $cibleId, $this->photos[0]),
            ['note' => 'Cadrage à revoir', 'mise_en_avant' => '1'],
        );
        $this->assertRedirect('/admin/selection-photos/index/moodboard/' . $cibleId);

        $ligne = TableRegistry::getTableLocator()->get('MoodboardsPhotos')->find()
            ->where(['moodboard_id' => $cibleId, 'photo_id' => $this->photos[0]])
            ->firstOrFail();

        $this->assertSame('Cadrage à revoir', $ligne->note);
        $this->assertTrue((bool)$ligne->mise_en_avant);

        // Case décochée : le champ caché envoie « 0 », la colonne est NOT NULL.
        $this->post(
            sprintf('/admin/selection-photos/annoter/moodboard/%d/%d', $cibleId, $this->photos[0]),
            ['note' => '', 'mise_en_avant' => '0'],
        );

        $apres = TableRegistry::getTableLocator()->get('MoodboardsPhotos')->find()
            ->where(['moodboard_id' => $cibleId, 'photo_id' => $this->photos[0]])
            ->firstOrFail();

        $this->assertFalse((bool)$apres->mise_en_avant);
    }

    /**
     * La couverture d'un album, et le refus d'en désigner une hors sélection.
     *
     * @return void
     */
    public function testCouvertureDAlbum(): void
    {
        $cibleId = $this->creerCible('album');
        $albums = TableRegistry::getTableLocator()->get('Albums');

        $this->post('/admin/selection-photos/basculer/album/' . $cibleId . '/' . $this->photos[0]);

        $this->post(sprintf('/admin/selection-photos/couverture/album/%d/%d', $cibleId, $this->photos[0]));
        $this->assertSame($this->photos[0], $albums->get($cibleId)->cover_photo_id);

        // Une photo absente de l'album ne peut pas en être la couverture : elle
        // s'afficherait en vitrine sans figurer dans l'album ouvert au clic.
        $this->post(sprintf('/admin/selection-photos/couverture/album/%d/%d', $cibleId, $this->photos[1]));
        $this->assertResponseCode(404);
        $this->assertSame($this->photos[0], $albums->get($cibleId)->cover_photo_id);

        $this->post(sprintf('/admin/selection-photos/couverture/album/%d/aucune', $cibleId));
        $this->assertNull($albums->get($cibleId)->cover_photo_id);
    }

    /**
     * Le lien « Définir en couverture » doit être servi par un vrai formulaire.
     *
     * Il vit à l'intérieur du formulaire de la sélection, ce que le HTML
     * interdit d'imbriquer : `postLink` met donc son formulaire en réserve dans
     * le bloc `postLink`, et le layout doit le ressortir. Tant qu'il ne le
     * faisait pas, le lien s'affichait et ne déclenchait rien — un test qui se
     * contente de poster l'URL, comme celui ci-dessus, ne voit pas la panne.
     *
     * @return void
     */
    public function testLeLienDeCouvertureEstRelieAUnFormulaire(): void
    {
        $cibleId = $this->creerCible('album');
        $this->post('/admin/selection-photos/basculer/album/' . $cibleId . '/' . $this->photos[0]);

        $this->get('/admin/selection-photos/index/album/' . $cibleId);
        $this->assertResponseOk();

        $action = sprintf('/admin/selection-photos/couverture/album/%d/%d', $cibleId, $this->photos[0]);
        $this->assertResponseContains('Définir en couverture');
        $this->assertMatchesRegularExpression(
            '#<form[^>]+action="' . preg_quote($action, '#') . '"#',
            (string)$this->_response->getBody(),
        );
    }

    /**
     * La couverture n'a d'utilité que si le photographe voit laquelle il a
     * choisie sans ouvrir chaque album.
     *
     * @return void
     */
    public function testLaListeDesAlbumsMontreLaCouverture(): void
    {
        $cibleId = $this->creerCible('album');
        $this->post('/admin/selection-photos/basculer/album/' . $cibleId . '/' . $this->photos[0]);
        $this->post(sprintf('/admin/selection-photos/couverture/album/%d/%d', $cibleId, $this->photos[0]));

        $this->get('/admin/albums');
        $this->assertResponseOk();
        $this->assertResponseContains(self::BASE_FICHIER . '1-thumb.jpeg');
    }

    /**
     * Un album sans couverture s'affiche sans image sur le site : la liste le
     * signale plutôt que de laisser croire à un oubli d'affichage.
     *
     * @return void
     */
    public function testUnAlbumSansCouvertureEstSignale(): void
    {
        $this->creerCible('album');

        $this->get('/admin/albums');
        $this->assertResponseOk();
        $this->assertResponseContains('sans<br>image');
    }

    /**
     * Faute de colonne de couverture, un moodboard est reconnaissable à sa
     * première photo — celle-là même qui ouvre la page partagée.
     *
     * @return void
     */
    public function testLaListeDesMoodboardsMontreUnApercu(): void
    {
        $cibleId = $this->creerCible('moodboard');
        $this->post('/admin/selection-photos/basculer/moodboard/' . $cibleId . '/' . $this->photos[1]);

        $this->get('/admin/moodboards');
        $this->assertResponseOk();
        $this->assertResponseContains(self::BASE_FICHIER . '2-thumb.jpeg');
    }

    /**
     * Le filtre de la photothèque : sur un millier de photos, retrouver celles
     * qui manquent à un album en parcourant les pages serait impraticable.
     *
     * @return void
     */
    public function testFiltreDeLaPhototheque(): void
    {
        $cibleId = $this->creerCible('album');

        // Une seule des trois photos est rattachée.
        $this->post('/admin/selection-photos/basculer/album/' . $cibleId . '/' . $this->photos[0]);

        // Les assertions visent l'identifiant des vignettes de la photothèque et
        // non le titre : celui-ci figure aussi dans le panneau de sélection du
        // haut, que le filtre ne concerne pas.
        $rattachee = sprintf('id="vignette-%d"', $this->photos[0]);
        $libre = sprintf('id="vignette-%d"', $this->photos[1]);

        // « Déjà rattachées » ne montre que la première.
        $this->get('/admin/selection-photos/index/album/' . $cibleId . '?filtre=liees');
        $this->assertResponseOk();
        $this->assertResponseContains($rattachee);
        $this->assertResponseNotContains($libre);

        // « Non rattachées » montre exactement l'inverse.
        $this->get('/admin/selection-photos/index/album/' . $cibleId . '?filtre=libres');
        $this->assertResponseOk();
        $this->assertResponseNotContains($rattachee);
        $this->assertResponseContains($libre);

        // Sans filtre, les trois sont là.
        $this->get('/admin/selection-photos/index/album/' . $cibleId);
        $this->assertResponseContains($rattachee);
        $this->assertResponseContains($libre);

        // Un filtre inventé retombe sur « toutes » plutôt que de renvoyer une
        // liste vide sans explication.
        $this->get('/admin/selection-photos/index/album/' . $cibleId . '?filtre=nimportequoi');
        $this->assertResponseOk();
        $this->assertResponseContains($rattachee);
        $this->assertResponseContains($libre);
    }

    /**
     * Les compteurs des onglets, et leur mise à jour hors bande après une
     * bascule : sans elle, les chiffres mentiraient jusqu'au rechargement.
     *
     * @return void
     */
    public function testCompteursDesOnglets(): void
    {
        $cibleId = $this->creerCible('album');

        $this->get('/admin/selection-photos/index/album/' . $cibleId);
        $this->assertResponseContains('Non rattachées');
        $this->assertResponseContains('Déjà rattachées');

        // Le bloc d'onglets ne doit apparaître qu'une fois : il porte un
        // identifiant, et chaque vignette a déjà rendu le sien par accident.
        $this->assertSame(
            1,
            substr_count((string)$this->_response->getBody(), 'id="onglets-phototheque"'),
            "Les onglets ne doivent être rendus qu'une fois sur la page.",
        );
        $this->assertResponseNotContains(
            'hx-swap-oob',
            "La page complète ne doit pas contenir d'échange hors bande.",
        );

        // En htmx, comme le fait le bouton : sans l'en-tête, l'action redirige
        // au lieu de renvoyer un fragment.
        $this->configRequest(['headers' => ['HX-Request' => 'true']]);
        $this->post('/admin/selection-photos/basculer/album/' . $cibleId . '/' . $this->photos[0]);

        // La réponse à la bascule embarque les onglets marqués `hx-swap-oob`.
        $this->assertResponseContains('hx-swap-oob="true"');
        $this->assertResponseContains('id="onglets-phototheque"');
        // Une photo vient d'être rattachée : le compteur « Déjà rattachées » le dit.
        $this->assertResponseContains('Déjà rattachées');
    }

    /**
     * Le filtre doit survivre à une recherche, et réciproquement : sinon taper
     * un mot ramènerait sur « Toutes » sans qu'on l'ait demandé.
     *
     * @return void
     */
    public function testLeFiltreEtLaRechercheSeConservent(): void
    {
        $cibleId = $this->creerCible('album');
        $this->post('/admin/selection-photos/basculer/album/' . $cibleId . '/' . $this->photos[0]);

        $this->get('/admin/selection-photos/index/album/' . $cibleId . '?filtre=libres');

        // Le champ caché du formulaire de recherche porte le filtre courant.
        $this->assertResponseContains('name="filtre" value="libres"');

        // Et les onglets conservent le terme cherché.
        $this->get('/admin/selection-photos/index/album/' . $cibleId . '?q=selection&filtre=libres');
        $this->assertResponseOk();
        $this->assertResponseContains('q=selection');
    }

    /**
     * Un type de liaison inconnu vient de l'URL : c'est une 404, pas une erreur
     * de programmation.
     *
     * @return void
     */
    public function testUnTypeInconnuRenvoieUne404(): void
    {
        $this->get('/admin/selection-photos/index/inexistant/1');

        $this->assertResponseCode(404);
    }
}
