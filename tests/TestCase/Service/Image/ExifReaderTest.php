<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Image;

use App\Service\Image\ExifReader;
use Cake\TestSuite\TestCase;

/**
 * La conversion GPS est la partie la plus facile à rater : l'EXIF stocke des
 * fractions en degrés/minutes/secondes avec l'hémisphère à part. Une erreur de
 * signe place une photo de Brazzaville en Europe du Nord sans que rien ne plante.
 */
class ExifReaderTest extends TestCase
{
    protected ExifReader $lecteur;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Sous-classe anonyme : les méthodes de conversion sont protégées, mais
        // ce sont elles qui portent la logique délicate.
        $this->lecteur = new class extends ExifReader {
            /**
             * @param mixed $coordonnee Coordonnée brute.
             * @param mixed $reference Hémisphère.
             * @return float|null
             */
            public function exposerVersDecimal(mixed $coordonnee, mixed $reference): ?float
            {
                return $this->versDecimal($coordonnee, $reference);
            }

            /**
             * @param mixed $valeur Fraction.
             * @return float|null
             */
            public function exposerFraction(mixed $valeur): ?float
            {
                return $this->fraction($valeur);
            }

            /**
             * @param mixed $valeur Date EXIF.
             * @return string|null
             */
            public function exposerDate(mixed $valeur): ?string
            {
                return $this->date($valeur);
            }

            /**
             * @param mixed $valeur Fraction.
             * @return string|null
             */
            public function exposerOuverture(mixed $valeur): ?string
            {
                return $this->ouverture($valeur);
            }
        };
    }

    /**
     * @return void
     */
    public function testFractionDecodeLesFormesEexif(): void
    {
        $this->assertSame(35.0, $this->lecteur->exposerFraction('35/1'));
        $this->assertSame(41.76, $this->lecteur->exposerFraction('4176/100'));
        $this->assertSame(2.8, $this->lecteur->exposerFraction(2.8));
        $this->assertNull($this->lecteur->exposerFraction('abc'));
        // Un dénominateur nul ne doit pas provoquer de division par zéro.
        $this->assertNull($this->lecteur->exposerFraction('10/0'));
    }

    /**
     * Kyoto : 35° 0' 41.76" N, 135° 46' 5.16" E.
     *
     * @return void
     */
    public function testCoordonneesNordEst(): void
    {
        $latitude = $this->lecteur->exposerVersDecimal(['35/1', '0/1', '4176/100'], 'N');
        $longitude = $this->lecteur->exposerVersDecimal(['135/1', '46/1', '516/100'], 'E');

        $this->assertEqualsWithDelta(35.0116, $latitude, 0.0001);
        $this->assertEqualsWithDelta(135.7681, $longitude, 0.0001);
    }

    /**
     * Brazzaville : 4° 15' 48.24" S, 15° 14' 34.44" E.
     *
     * Le cas qui compte : sans l'inversion de signe sur « S », la photo
     * remonterait de 4 degrés au nord de l'équateur.
     *
     * @return void
     */
    public function testCoordonneesSudSontNegatives(): void
    {
        $latitude = $this->lecteur->exposerVersDecimal(['4/1', '15/1', '4824/100'], 'S');

        $this->assertLessThan(0, $latitude);
        $this->assertEqualsWithDelta(-4.2634, $latitude, 0.0001);
    }

    /**
     * @return void
     */
    public function testCoordonneesOuestSontNegatives(): void
    {
        $longitude = $this->lecteur->exposerVersDecimal(['74/1', '0/1', '2160/100'], 'W');

        $this->assertLessThan(0, $longitude);
    }

    /**
     * @return void
     */
    public function testCoordonneesIncompletesSontIgnorees(): void
    {
        $this->assertNull($this->lecteur->exposerVersDecimal(['35/1'], 'N'));
        $this->assertNull($this->lecteur->exposerVersDecimal(null, 'N'));
        $this->assertNull($this->lecteur->exposerVersDecimal('35', 'N'));
    }

    /**
     * Un boîtier dont l'horloge n'a jamais été réglée écrit des zéros : mieux
     * vaut aucune date qu'une date absurde en base.
     *
     * @return void
     */
    public function testDateInvalideEstIgnoree(): void
    {
        $this->assertNull($this->lecteur->exposerDate('0000:00:00 00:00:00'));
        $this->assertNull($this->lecteur->exposerDate(''));
        $this->assertNull($this->lecteur->exposerDate(null));
        $this->assertSame('2026-03-14 09:30:00', $this->lecteur->exposerDate('2026:03:14 09:30:00'));
    }

    /**
     * @return void
     */
    public function testOuvertureLisible(): void
    {
        $this->assertSame('f/2.8', $this->lecteur->exposerOuverture('28/10'));
        $this->assertSame('f/4', $this->lecteur->exposerOuverture('4/1'));
        $this->assertNull($this->lecteur->exposerOuverture(null));
    }

    /**
     * Un fichier sans EXIF est un cas normal, pas une erreur : il ne doit ni
     * lever d'exception ni polluer la sortie de notices.
     *
     * @return void
     */
    public function testFichierSansExifRenvoieUnTableauVide(): void
    {
        $chemin = TMP . 'sans-exif.jpg';
        $image = imagecreatetruecolor(10, 10);
        imagejpeg($image, $chemin);
        imagedestroy($image);

        $this->assertSame([], (new ExifReader())->lire($chemin));

        unlink($chemin);
    }

    /**
     * @return void
     */
    public function testFichierInexistantRenvoieUnTableauVide(): void
    {
        $this->assertSame([], (new ExifReader())->lire('/chemin/qui/n/existe/pas.jpg'));
    }
}
