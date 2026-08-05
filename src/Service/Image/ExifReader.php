<?php
declare(strict_types=1);

namespace App\Service\Image;

use DateTimeImmutable;

/**
 * Lecture des métadonnées EXIF d'une photo.
 *
 * L'ancien site appelait `exif_read_data()` puis accédait directement aux clés
 * (`$exif['Model']`, `$exif['UndefinedTag:0xA434']`…) : le moindre fichier sans
 * EXIF — un export web, un scan — déclenchait une cascade de notices PHP.
 * Ici tout accès est défensif et le résultat est normalisé.
 */
class ExifReader
{
    /**
     * @param string $chemin Chemin absolu du fichier.
     * @return array<string, mixed> Champs prêts à alimenter l'entité Exif.
     */
    public function lire(string $chemin): array
    {
        if (!is_file($chemin) || !function_exists('exif_read_data')) {
            return [];
        }

        // Le `@` est ici volontaire : `exif_read_data()` émet un warning sur tout
        // fichier sans bloc EXIF, ce qui est un cas parfaitement normal et non
        // une erreur. Les autres appels du projet ne masquent rien.
        $exif = @exif_read_data($chemin, null, true);

        if ($exif === false) {
            return [];
        }

        $ifd0 = $exif['IFD0'] ?? [];
        $sousIfd = $exif['EXIF'] ?? [];
        $gps = $exif['GPS'] ?? [];

        $donnees = [
            'apn' => $this->texte($ifd0['Model'] ?? null),
            // Tag standard de l'objectif ; certains boîtiers ne renseignent que
            // la forme numérique brute.
            'objectif' => $this->texte($sousIfd['UndefinedTag:0xA434'] ?? $sousIfd['LensModel'] ?? null),
            'ouverture' => $this->ouverture($sousIfd['FNumber'] ?? null),
            'iso' => $this->entier($sousIfd['ISOSpeedRatings'] ?? null),
            'exposition' => $this->texte($sousIfd['ExposureTime'] ?? null),
            'focale' => $this->focale($sousIfd['FocalLength'] ?? null),
            'artiste' => $this->texte($ifd0['Artist'] ?? null),
            'copyright' => $this->texte($ifd0['Copyright'] ?? null),
            'date_capture' => $this->date($sousIfd['DateTimeOriginal'] ?? $ifd0['DateTime'] ?? null),
        ];

        $coordonnees = $this->coordonnees($gps);

        return array_filter(
            $donnees + $coordonnees,
            static fn($valeur): bool => $valeur !== null,
        );
    }

    /**
     * Convertit les coordonnées GPS en degrés décimaux.
     *
     * L'EXIF stocke la position en degrés/minutes/secondes sous forme de
     * fractions (« 35/1 », « 0/1 », « 4176/100 »), avec l'hémisphère dans un
     * champ séparé. Sans cette conversion les valeurs sont inexploitables par
     * une carte — c'est pour cela que l'ancien site n'affichait rien.
     *
     * @param array<string, mixed> $gps Section GPS de l'EXIF.
     * @return array<string, float|null>
     */
    protected function coordonnees(array $gps): array
    {
        $latitude = $this->versDecimal($gps['GPSLatitude'] ?? null, $gps['GPSLatitudeRef'] ?? null);
        $longitude = $this->versDecimal($gps['GPSLongitude'] ?? null, $gps['GPSLongitudeRef'] ?? null);

        return [
            'gps_lat' => $latitude,
            'gps_lng' => $longitude,
            'gps_altitude' => isset($gps['GPSAltitude'])
                ? $this->fraction($gps['GPSAltitude'])
                : null,
        ];
    }

    /**
     * @param mixed $coordonnee Tableau [degrés, minutes, secondes] en fractions.
     * @param mixed $reference Hémisphère : N, S, E ou W.
     * @return float|null
     */
    protected function versDecimal(mixed $coordonnee, mixed $reference): ?float
    {
        if (!is_array($coordonnee) || count($coordonnee) < 3) {
            return null;
        }

        $degres = $this->fraction($coordonnee[0]);
        $minutes = $this->fraction($coordonnee[1]);
        $secondes = $this->fraction($coordonnee[2]);

        if ($degres === null || $minutes === null || $secondes === null) {
            return null;
        }

        $decimal = $degres + $minutes / 60 + $secondes / 3600;

        // Sud et Ouest sont négatifs. Sans ce signe, une photo prise à
        // Brazzaville apparaîtrait dans l'hémisphère nord.
        if (in_array(strtoupper((string)$reference), ['S', 'W'], true)) {
            $decimal = -$decimal;
        }

        return round($decimal, 7);
    }

    /**
     * @param mixed $valeur Fraction « numérateur/dénominateur » ou nombre.
     * @return float|null
     */
    protected function fraction(mixed $valeur): ?float
    {
        if (is_numeric($valeur)) {
            return (float)$valeur;
        }

        if (!is_string($valeur) || !str_contains($valeur, '/')) {
            return null;
        }

        [$numerateur, $denominateur] = array_pad(explode('/', $valeur, 2), 2, '1');

        if (!is_numeric($numerateur) || !is_numeric($denominateur) || (float)$denominateur === 0.0) {
            return null;
        }

        return (float)$numerateur / (float)$denominateur;
    }

    /**
     * @param mixed $valeur Fraction EXIF.
     * @return string|null Ouverture lisible, ex. « f/2.8 ».
     */
    protected function ouverture(mixed $valeur): ?string
    {
        $nombre = $this->fraction($valeur);

        return $nombre === null ? null : sprintf('f/%s', rtrim(rtrim(number_format($nombre, 1), '0'), '.'));
    }

    /**
     * @param mixed $valeur Fraction EXIF.
     * @return string|null Focale lisible, ex. « 35mm ».
     */
    protected function focale(mixed $valeur): ?string
    {
        $nombre = $this->fraction($valeur);

        return $nombre === null ? null : sprintf('%dmm', (int)round($nombre));
    }

    /**
     * @param mixed $valeur Date EXIF au format « Y:m:d H:i:s ».
     * @return string|null
     */
    protected function date(mixed $valeur): ?string
    {
        if (!is_string($valeur) || trim($valeur) === '') {
            return null;
        }

        $brut = trim($valeur);
        $date = DateTimeImmutable::createFromFormat('Y:m:d H:i:s', $brut);

        if ($date === false) {
            return null;
        }

        // Certains boîtiers laissent « 0000:00:00 00:00:00 » quand l'horloge n'a
        // jamais été réglée. PHP ne rejette pas cette valeur : il la « corrige »
        // silencieusement en -0001-11-30. On vérifie donc que la date reformatée
        // redonne exactement l'entrée — seule une date réellement valide le fait.
        if ($date->format('Y:m:d H:i:s') !== $brut) {
            return null;
        }

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param mixed $valeur Valeur brute.
     * @return string|null
     */
    protected function texte(mixed $valeur): ?string
    {
        if (!is_scalar($valeur)) {
            return null;
        }

        $texte = trim((string)$valeur);

        return $texte === '' ? null : $texte;
    }

    /**
     * @param mixed $valeur Valeur brute.
     * @return int|null
     */
    protected function entier(mixed $valeur): ?int
    {
        if (is_array($valeur)) {
            $valeur = $valeur[0] ?? null;
        }

        return is_numeric($valeur) ? (int)$valeur : null;
    }
}
