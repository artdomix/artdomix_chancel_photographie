<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Les séries du portfolio et la circulation entre leurs photos.
 *
 * Une photo isolée était jusqu'ici un cul-de-sac : c'est pourtant la page que
 * les moteurs indexent et que l'on partage. Et l'ordre choisi par le photographe
 * dans l'administration n'était pas celui du site, qui triait par date.
 */
class SerieEtNavigationTest extends TestCase
{
    use IntegrationTestTrait;

    protected const PREFIXE = 'photo-serie-';

    protected const ALBUM = 'Série de test';

    /**
     * @var list<int>
     */
    protected array $photos = [];

    protected int $idAlbum = 0;

    protected string $slugAlbum = '';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->nettoyer();

        $photos = TableRegistry::getTableLocator()->get('Photos');

        // Trois photos, créées dans cet ordre : leur chronologie est donc
        // 3, 2, 1 — l'inverse de l'ordre qu'on imposera plus bas.
        foreach ([1, 2, 3] as $i) {
            $photo = $photos->newEntity(['titre' => 'Photo de série ' . $i]);
            $photo->set('uuid', Text::uuid());
            $photo->set('fichier', self::PREFIXE . $i);
            $photo->set('extension_origine', 'jpg');
            $photo->set('largeur', 1600);
            $photo->set('hauteur', 1067);
            $photo->set('actif', true);
            $photos->saveOrFail($photo);
            $this->photos[] = $photo->id;
        }

        $albums = TableRegistry::getTableLocator()->get('Albums');
        $album = $albums->newEntity([
            'nom' => self::ALBUM,
            'chapeau' => 'Trois images, un propos.',
            'description' => "Le texte d'intention de la série.",
            'visibilite' => 'public',
            'actif' => true,
        ]);
        $album->set('cover_photo_id', $this->photos[0]);
        $albums->saveOrFail($album);

        $this->idAlbum = $album->id;
        $this->slugAlbum = $album->slug;

        // Ordre voulu : 1, 2, 3 — soit l'inverse de la chronologie.
        $jonction = TableRegistry::getTableLocator()->get('AlbumsPhotos');
        $lignes = [];

        foreach ($this->photos as $position => $photoId) {
            $lignes[] = ['album_id' => $album->id, 'photo_id' => $photoId, 'ordre' => $position];
        }

        $jonction->saveManyOrFail($jonction->newEntities($lignes));
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $this->nettoyer();

        parent::tearDown();
    }

    /**
     * @return void
     */
    protected function nettoyer(): void
    {
        TableRegistry::getTableLocator()->get('Albums')->deleteAll(['nom' => self::ALBUM]);
        TableRegistry::getTableLocator()->get('Photos')
            ->deleteAll(['fichier LIKE' => self::PREFIXE . '%']);
    }

    /**
     * @param int $index Rang de la photo dans la série.
     * @return string
     */
    protected function slug(int $index): string
    {
        return TableRegistry::getTableLocator()->get('Photos')->get($this->photos[$index])->slug;
    }

    /**
     * L'ordre du site doit être celui que le photographe a composé, pas la date
     * de prise de vue : c'est ce qui distingue une série d'un stock d'images.
     *
     * @return void
     */
    public function testLaSerieSuitLOrdreChoisiEtNonLaChronologie(): void
    {
        $this->get('/portfolio/' . $this->slugAlbum);
        $this->assertResponseOk();

        $corps = (string)$this->_response->getBody();
        $positions = [];

        foreach ([0, 1, 2] as $index) {
            $positions[] = strpos($corps, self::PREFIXE . ($index + 1));
        }

        $this->assertNotContains(false, $positions, 'Les trois photos devraient être affichées.');
        $this->assertSame(
            $positions,
            [$positions[0], $positions[1], $positions[2]],
            'Sécurité du test.',
        );
        $this->assertLessThan($positions[1], $positions[0], 'La photo 1 devrait précéder la 2.');
        $this->assertLessThan($positions[2], $positions[1], 'La photo 2 devrait précéder la 3.');
    }

    /**
     * La série se présente par son image d'ouverture et son chapô.
     *
     * @return void
     */
    public function testLaSerieSePresenteParSonOuverture(): void
    {
        $this->get('/portfolio/' . $this->slugAlbum);

        $this->assertResponseOk();
        $this->assertResponseContains('Trois images, un propos.');
        // Sans l'apostrophe, que `h()` transforme en entité dans la page.
        $this->assertResponseContains('intention de la série.');
        // La photo de couverture est chargée sans différé : c'est elle que mesure
        // le LCP, la différer dégraderait la note de performance.
        $this->assertResponseContains(self::PREFIXE . '1-large');
    }

    /**
     * Précédent et suivant suivent l'ordre de la série.
     *
     * @return void
     */
    public function testUnePhotoMeneAuxSuivantesEtPrecedentes(): void
    {
        // Première photo : pas de précédente, une suivante.
        $this->get('/photo/' . $this->slug(0) . '?serie=' . $this->slugAlbum);
        $this->assertResponseOk();
        $this->assertResponseContains('rel="next"');
        $this->assertResponseNotContains('rel="prev"');

        // Photo du milieu : les deux.
        $this->get('/photo/' . $this->slug(1) . '?serie=' . $this->slugAlbum);
        $this->assertResponseContains('rel="prev"');
        $this->assertResponseContains('rel="next"');
        $this->assertResponseContains('/photo/' . $this->slug(0));
        $this->assertResponseContains('/photo/' . $this->slug(2));

        // Dernière : pas de suivante.
        $this->get('/photo/' . $this->slug(2) . '?serie=' . $this->slugAlbum);
        $this->assertResponseContains('rel="prev"');
        $this->assertResponseNotContains('rel="next"');
    }

    /**
     * Sans paramètre, la page retrouve seule la série de la photo : un visiteur
     * venu d'un moteur de recherche n'a pas d'historique.
     *
     * @return void
     */
    public function testLaSerieEstRetrouveeSansParametre(): void
    {
        $this->get('/photo/' . $this->slug(1));

        $this->assertResponseOk();
        $this->assertResponseContains('Dans la même série');
        $this->assertResponseContains(self::ALBUM);
        $this->assertResponseContains('/portfolio/' . $this->slugAlbum);
    }

    /**
     * Un slug de série inventé ne doit pas être suivi : la page retombe sur une
     * série réelle de la photo plutôt que d'afficher un contexte fabriqué.
     *
     * @return void
     */
    public function testUneSerieInventeeEstIgnoree(): void
    {
        $this->get('/photo/' . $this->slug(1) . '?serie=serie-qui-nexiste-pas');

        $this->assertResponseOk();
        $this->assertResponseContains(self::ALBUM);
    }

    /**
     * Depuis la série, chaque vignette doit offrir un chemin vers la fiche, et
     * ce chemin doit conserver la série parcourue.
     *
     * @return void
     */
    public function testLesVignettesMenentALaFicheEnGardantLaSerie(): void
    {
        $this->get('/portfolio/' . $this->slugAlbum);

        $this->assertResponseContains(sprintf(
            'data-lightbox-fiche="/photo/%s?serie=%s"',
            $this->slug(0),
            $this->slugAlbum,
        ));
    }
}
