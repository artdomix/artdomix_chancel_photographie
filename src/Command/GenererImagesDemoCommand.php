<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Image\DerivativeGenerator;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Fabrique des images de remplacement pour les photos du jeu de démonstration.
 *
 * Le dépôt ne contient aucune image — c'est une règle du projet. Sans cette
 * commande, un site fraîchement installé afficherait des cadres vides. Les
 * visuels sont générés à la volée puis passés dans le **vrai** générateur de
 * dérivés : le seed reste purement SQL et le pipeline est exercé au passage.
 */
class GenererImagesDemoCommand extends Command
{
    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Analyseur d'options.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(
                'Génère des images de remplacement pour les photos de démonstration.',
            )
            ->addOption('force', [
                'short' => 'f',
                'boolean' => true,
                'help' => 'Régénère même si les dérivés existent déjà.',
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments de la commande.
     * @param \Cake\Console\ConsoleIo $io Entrées/sorties console.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $photos = $this->fetchTable('Photos');
        $racine = WWW_ROOT . 'media' . DS . 'photos';
        $generateur = new DerivativeGenerator($racine);

        $liste = $photos->find()->orderBy(['id' => 'ASC'])->all();

        if ($liste->count() === 0) {
            $io->warning('Aucune photo en base. Lance d\'abord : bin/cake seeds run DemoSeed');

            return static::CODE_SUCCESS;
        }

        $io->out(sprintf('Génération pour %d photos...', $liste->count()));

        $traitees = 0;

        foreach ($liste as $photo) {
            $temoin = $generateur->cheminVariante($photo->fichier, 'grid', 'jpeg');

            if (is_file($temoin) && !$args->getOption('force')) {
                $io->verbose(sprintf('  %s : déjà présent', $photo->slug));

                continue;
            }

            $source = $this->fabriquerImage($photo->id, (string)($photo->titre ?? $photo->slug));

            try {
                $derives = $generateur->generer($source, $photo->fichier);

                $photo->largeur = $derives->largeur;
                $photo->hauteur = $derives->hauteur;
                $photo->lqip = $derives->lqip;
                $photo->couleur_dominante = $derives->couleurDominante;
                $photo->has_avif = $derives->avif;
                $photo->has_webp = $derives->webp;

                $photos->save($photo);
                $traitees++;

                $io->out(sprintf('  %s : %d fichiers', $photo->slug, count($derives->fichiers)));
            } finally {
                // Le fichier temporaire part quoi qu'il arrive.
                if (is_file($source)) {
                    unlink($source);
                }
            }
        }

        $io->success(sprintf('%d photos traitées.', $traitees));

        return static::CODE_SUCCESS;
    }

    /**
     * Compose un dégradé identifiable, différent pour chaque photo.
     *
     * @param int $graine Identifiant de la photo, utilisé comme graine.
     * @param string $libelle Texte inscrit sur l'image.
     * @return string Chemin du fichier temporaire.
     */
    protected function fabriquerImage(int $graine, string $libelle): string
    {
        // Les photos d'identifiant pair sont en paysage, les autres en portrait :
        // le rendu de la grille est ainsi testé dans les deux orientations.
        $paysage = $graine % 2 === 0;
        $largeur = $paysage ? 2400 : 1600;
        $hauteur = $paysage ? 1600 : 2400;

        $image = imagecreatetruecolor($largeur, $hauteur);
        $teinte = ($graine * 47) % 360;

        for ($y = 0; $y < $hauteur; $y += 4) {
            $facteur = $y / $hauteur;
            [$r, $v, $b] = $this->hslVersRgb($teinte / 360, 0.35, 0.22 + $facteur * 0.35);
            $couleur = imagecolorallocate($image, $r, $v, $b);
            imagefilledrectangle($image, 0, $y, $largeur, $y + 4, $couleur);
        }

        $blanc = imagecolorallocatealpha($image, 255, 255, 255, 70);
        imagestring($image, 5, 40, $hauteur - 60, $libelle, $blanc);

        $chemin = TMP . 'demo-' . $graine . '.jpg';
        imagejpeg($image, $chemin, 92);
        imagedestroy($image);

        return $chemin;
    }

    /**
     * @param float $h Teinte (0-1).
     * @param float $s Saturation (0-1).
     * @param float $l Luminosité (0-1).
     * @return array{int, int, int}
     */
    protected function hslVersRgb(float $h, float $s, float $l): array
    {
        if ($s === 0.0) {
            $gris = (int)round($l * 255);

            return [$gris, $gris, $gris];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $canal = static function (float $t) use ($p, $q): int {
            $t = fmod($t + 1.0, 1.0);

            $valeur = match (true) {
                $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                $t < 1 / 2 => $q,
                $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                default => $p,
            };

            return (int)round($valeur * 255);
        };

        return [$canal($h + 1 / 3), $canal($h), $canal($h - 1 / 3)];
    }
}
