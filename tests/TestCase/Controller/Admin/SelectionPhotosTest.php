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
