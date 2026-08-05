<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Image;

use App\Service\Image\DerivativeGenerator;
use App\Service\Image\VarianteImage;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Vérifie le pipeline de dérivés sur de vraies images.
 */
class DerivativeGeneratorTest extends TestCase
{
    protected string $sortie;

    protected DerivativeGenerator $generateur;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->sortie = TMP . 'derives-test-' . uniqid();
        mkdir($this->sortie, 0755, true);
        $this->generateur = new DerivativeGenerator($this->sortie);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        foreach (glob($this->sortie . '/*') ?: [] as $fichier) {
            unlink($fichier);
        }

        if (is_dir($this->sortie)) {
            rmdir($this->sortie);
        }

        parent::tearDown();
    }

    /**
     * @return string
     */
    protected function fixture(string $nom): string
    {
        return TESTS . 'Fixture' . DS . 'images' . DS . $nom;
    }

    /**
     * @return void
     */
    public function testGenereToutesLesVariantesDansTousLesFormats(): void
    {
        $resultat = $this->generateur->generer($this->fixture('paysage.jpg'), 'essai');

        $formatsAttendus = 1 + (DerivativeGenerator::supporteWebp() ? 1 : 0) + (DerivativeGenerator::supporteAvif() ? 1 : 0);
        $attendu = count(VarianteImage::toutes()) * $formatsAttendus;

        $this->assertCount($attendu, $resultat->fichiers);

        foreach ($resultat->fichiers as $fichier) {
            $this->assertFileExists($fichier);
            $this->assertGreaterThan(0, filesize($fichier));
        }
    }

    /**
     * @return void
     */
    public function testConserveLeRatioEnPaysage(): void
    {
        $this->generateur->generer($this->fixture('paysage.jpg'), 'paysage');

        [$largeur, $hauteur] = getimagesize($this->generateur->cheminVariante('paysage', 'grid', 'jpeg'));

        $this->assertSame(640, $largeur);
        // 2400x1600 -> ratio 1,5 : 640 de large donne 427 de haut (arrondi).
        $this->assertEqualsWithDelta(640 / 1.5, $hauteur, 1.0);
    }

    /**
     * @return void
     */
    public function testConserveLeRatioEnPortrait(): void
    {
        $resultat = $this->generateur->generer($this->fixture('portrait.jpg'), 'portrait');

        $this->assertTrue($resultat->estPortrait());

        [$largeur, $hauteur] = getimagesize($this->generateur->cheminVariante('portrait', 'grid', 'jpeg'));

        $this->assertSame(640, $largeur);
        $this->assertGreaterThan($largeur, $hauteur);
    }

    /**
     * Agrandir une image ne lui ajoute aucun détail : cela ne ferait qu'alourdir
     * le fichier servi au visiteur.
     *
     * @return void
     */
    public function testNAgranditJamaisUnePetiteImage(): void
    {
        $this->generateur->generer($this->fixture('portrait.jpg'), 'petit');

        // La source fait 1200 px de large, la variante `large` vise 2048 px.
        [$largeur] = getimagesize($this->generateur->cheminVariante('petit', 'large', 'jpeg'));

        $this->assertSame(1200, $largeur);
    }

    /**
     * @return void
     */
    public function testProduitUnLqipEtUneCouleurDominante(): void
    {
        $resultat = $this->generateur->generer($this->fixture('portrait.jpg'), 'meta');

        $this->assertStringStartsWith('data:image/jpeg;base64,', $resultat->lqip);
        // Le placeholder doit rester minuscule : il est inséré dans le HTML.
        $this->assertLessThan(2048, strlen($resultat->lqip));
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $resultat->couleurDominante);
    }

    /**
     * @return void
     */
    public function testSupprimeToutesLesDeclinaisons(): void
    {
        $resultat = $this->generateur->generer($this->fixture('paysage.jpg'), 'aeffacer');

        $supprimes = $this->generateur->supprimer('aeffacer');

        $this->assertSame(count($resultat->fichiers), $supprimes);
        $this->assertCount(0, glob($this->sortie . '/*') ?: []);
    }

    /**
     * L'ancien site appelait `unlink()` sans vérifier l'existence du fichier et
     * émettait un warning dès qu'une déclinaison manquait.
     *
     * @return void
     */
    public function testSupprimerDeuxFoisNeProduitAucuneErreur(): void
    {
        $this->generateur->generer($this->fixture('paysage.jpg'), 'double');
        $this->generateur->supprimer('double');

        $this->assertSame(0, $this->generateur->supprimer('double'));
    }

    /**
     * @return void
     */
    public function testOriginalIntrouvableLeveUneException(): void
    {
        $this->expectException(RuntimeException::class);

        $this->generateur->generer('/chemin/inexistant.jpg', 'nope');
    }

    /**
     * Le générateur ne doit rien afficher : l'ancien composant `ResizeImg`
     * ponctuait chaque étape d'un `echo` qui se retrouvait dans le HTML.
     *
     * @return void
     */
    public function testNeProduitAucuneSortie(): void
    {
        ob_start();
        $this->generateur->generer($this->fixture('portrait.jpg'), 'silencieux');
        $sortie = ob_get_clean();

        $this->assertSame('', $sortie);
    }
}
