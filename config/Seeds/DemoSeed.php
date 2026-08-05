<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Utility\Text;
use Migrations\BaseSeed;

/**
 * Jeu de données de démonstration.
 *
 * Le projet repart de zéro : ce seed sert à disposer d'un site navigable en
 * développement. Les fichiers images correspondants sont produits séparément par
 * `bin/cake generer_images_demo`, qui réutilise le vrai générateur de dérivés
 * plutôt que de dupliquer la logique de traitement ici.
 *
 * À ne jamais exécuter en production : les mots de passe sont publics.
 */
class DemoSeed extends BaseSeed
{
    /**
     * @return void
     */
    public function run(): void
    {
        $maintenant = date('Y-m-d H:i:s');
        $hasher = new DefaultPasswordHasher();

        $this->table('users')->insert([
            [
                'id' => 1,
                'email' => 'admin@chancel.test',
                'password' => $hasher->hash('admin-demo-2026'),
                'role' => 'admin',
                'prenom' => 'Dominique',
                'nom' => 'Chancel',
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 2,
                'email' => 'client@chancel.test',
                'password' => $hasher->hash('client-demo-2026'),
                'role' => 'member',
                'prenom' => 'Camille',
                'nom' => 'Durand',
                'societe' => 'Studio Durand',
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
        ])->save();

        $this->table('demandes')->insert([
            [
                'id' => 1,
                'nom' => 'Shooting portrait',
                'slug' => 'shooting-portrait',
                'ordre' => 1,
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 2,
                'nom' => 'Reportage',
                'slug' => 'reportage',
                'ordre' => 2,
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 3,
                'nom' => 'Achat de tirage',
                'slug' => 'achat-tirage',
                'ordre' => 3,
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 4,
                'nom' => 'Presse et partenariats',
                'slug' => 'presse',
                'ordre' => 4,
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
        ])->save();

        $this->table('config')->insert([
            [
                'cle' => 'site.titre',
                'valeur' => 'Chancel Photographie',
                'libelle' => 'Titre du site',
                'type' => 'texte',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'cle' => 'site.email',
                'valeur' => 'contact@chancel.art-domix.fr',
                'libelle' => 'E-mail de contact',
                'type' => 'texte',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'cle' => 'images.filigrane',
                'valeur' => '0',
                'libelle' => 'Filigrane sur les grands formats',
                'type' => 'booleen',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'cle' => 'images.filigrane_texte',
                'valeur' => '© Chancel',
                'libelle' => 'Texte du filigrane',
                'type' => 'texte',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
        ])->save();

        $this->table('tags')->insert([
            [
                'id' => 1,
                'nom' => 'Portrait',
                'slug' => 'portrait',
                'type' => 'sujet',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 2,
                'nom' => 'Japon',
                'slug' => 'japon',
                'type' => 'lieu',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 3,
                'nom' => 'Congo',
                'slug' => 'congo',
                'type' => 'lieu',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 4,
                'nom' => 'Noir et blanc',
                'slug' => 'noir-et-blanc',
                'type' => 'technique',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 5,
                'nom' => 'Automobile',
                'slug' => 'automobile',
                'type' => 'sujet',
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
        ])->save();

        $this->insererAlbums($maintenant);
        $this->insererPhotos($maintenant);

        $this->table('articles')->insert([
            [
                'id' => 1,
                'titre' => 'Carnet de route : deux semaines au Japon',
                'slug' => 'carnet-de-route-japon',
                'chapeau' => 'Kyoto sous la pluie, Tokyo de nuit, et beaucoup de marche.',
                'contenu' => '<p>Le récit détaillé arrive bientôt.</p>',
                'photo_id' => 3,
                'auteur_id' => 1,
                'publie_le' => $maintenant,
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
        ])->save();

        $this->insererMoodboards($maintenant, $hasher);
        $this->insererGalerie($maintenant, $hasher);
    }

    /**
     * Arbre d'albums.
     *
     * Les bornes `lft`/`rght` sont posées ici à la main, en parcours préfixe, parce
     * que le seed écrit en SQL direct sans passer par le TreeBehavior. Si l'arbre
     * évolue, plutôt que de recalculer : `$this->Albums->recover()`.
     *
     *  Work (1..12)
     *    Portrait (2..3)
     *    Voyage (4..9)
     *      Japon (5..6)
     *      Congo (7..8)
     *    Automobile (10..11)
     *  Personnel (13..14)
     *
     * @param string $maintenant Horodatage commun aux lignes insérées.
     * @return void
     */
    protected function insererAlbums(string $maintenant): void
    {
        $albums = [
            [1, 'Work', 'work', null, 1, 12],
            [2, 'Portrait', 'portrait', 1, 2, 3],
            [3, 'Voyage', 'voyage', 1, 4, 9],
            [4, 'Japon', 'japon', 3, 5, 6],
            [5, 'Congo', 'congo', 3, 7, 8],
            [6, 'Automobile', 'automobile', 1, 10, 11],
            [7, 'Personnel', 'personnel', null, 13, 14],
        ];

        $lignes = [];

        foreach ($albums as $i => [$id, $nom, $slug, $parent, $lft, $rght]) {
            $lignes[] = [
                'id' => $id,
                'nom' => $nom,
                'slug' => $slug,
                'parent_id' => $parent,
                'lft' => $lft,
                'rght' => $rght,
                'visibilite' => $slug === 'personnel' ? 'prive' : 'public',
                'ordre' => $i,
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ];
        }

        $this->table('albums')->insert($lignes)->save();
    }

    /**
     * Photos de démonstration, avec leurs EXIF et leurs rattachements.
     *
     * @param string $maintenant Horodatage commun aux lignes insérées.
     * @return void
     */
    protected function insererPhotos(string $maintenant): void
    {
        $photos = [
            [1, 'Regard', 'regard', 'Portrait en lumière naturelle', 2, [1, 4]],
            [2, 'Contre-jour', 'contre-jour', 'Portrait à contre-jour', 2, [1]],
            [3, 'Ruelle de Kyoto', 'ruelle-de-kyoto', 'Kyoto sous la pluie', 4, [2]],
            [4, 'Torii', 'torii', 'Fushimi Inari au petit matin', 4, [2, 4]],
            [5, 'Marché de Brazzaville', 'marche-de-brazzaville', 'Couleurs du marché', 5, [3]],
            [6, 'Ligne de fuite', 'ligne-de-fuite', 'Détail de carrosserie', 6, [5, 4]],
        ];

        $lignesPhotos = [];
        $lignesExifs = [];
        $lignesAlbums = [];
        $lignesTags = [];

        foreach ($photos as $i => [$id, $titre, $slug, $description, $albumId, $tags]) {
            $lignesPhotos[] = [
                'id' => $id,
                'uuid' => Text::uuid(),
                'titre' => $titre,
                'slug' => $slug,
                'description' => $description,
                'alt' => $description,
                'fichier' => sprintf('demo-%02d', $id),
                'extension_origine' => 'jpg',
                'largeur' => 2400,
                'hauteur' => 1600,
                'couleur_dominante' => '#2d2d2d',
                'has_avif' => false,
                'has_webp' => false,
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ];

            $lignesExifs[] = [
                'photo_id' => $id,
                'apn' => 'Canon EOS R6',
                'objectif' => 'RF 35mm F1.8',
                'ouverture' => 'f/2.8',
                'iso' => 400,
                'exposition' => '1/250',
                'focale' => '35mm',
                'artiste' => 'Chancel',
                'copyright' => '© Chancel',
                'date_capture' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                // Deux photos géolocalisées pour que la carte ait de quoi afficher.
                'gps_lat' => $id === 3 ? 35.0116 : ($id === 5 ? -4.2634 : null),
                'gps_lng' => $id === 3 ? 135.7681 : ($id === 5 ? 15.2429 : null),
                'created' => $maintenant,
                'modified' => $maintenant,
            ];

            $lignesAlbums[] = ['album_id' => $albumId, 'photo_id' => $id, 'ordre' => $i];

            // Les photos des sous-albums remontent aussi dans l'album racine « Work » :
            // c'est précisément ce que la relation N-N permet et que l'ancien schéma,
            // avec son unique `album_id`, interdisait.
            if ($albumId !== 7) {
                $lignesAlbums[] = ['album_id' => 1, 'photo_id' => $id, 'ordre' => $i];
            }

            foreach ($tags as $tagId) {
                $lignesTags[] = ['photo_id' => $id, 'tag_id' => $tagId];
            }
        }

        $this->table('photos')->insert($lignesPhotos)->save();
        $this->table('exifs')->insert($lignesExifs)->save();
        $this->table('albums_photos')->insert($lignesAlbums)->save();
        $this->table('photos_tags')->insert($lignesTags)->save();

        // Couvertures posées après coup : les photos doivent exister avant d'être
        // référencées par la clé étrangère.
        $this->execute('UPDATE albums SET cover_photo_id = 1 WHERE id = 2');
        $this->execute('UPDATE albums SET cover_photo_id = 3 WHERE id = 4');
    }

    /**
     * Un moodboard accessible par lien et un moodboard privé sous mot de passe.
     *
     * @param string $maintenant Horodatage commun aux lignes insérées.
     * @param \Authentication\PasswordHasher\DefaultPasswordHasher $hasher Hasher des mots de passe.
     * @return void
     */
    protected function insererMoodboards(string $maintenant, DefaultPasswordHasher $hasher): void
    {
        $this->table('moodboards')->insert([
            [
                'id' => 1,
                'titre' => 'Inspiration mariage automne',
                'slug' => 'inspiration-mariage-automne',
                'description' => 'Palette chaude, lumière rasante.',
                'theme' => 'mosaique-flip',
                'visibilite' => 'lien',
                'share_token' => 'demo-lien-mariage-automne-2026',
                'user_id' => 1,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
            [
                'id' => 2,
                'titre' => 'Série privée — sélection client',
                'slug' => 'serie-privee-selection-client',
                'theme' => 'mur-parallaxe',
                'visibilite' => 'prive',
                'share_token' => 'demo-prive-selection-client-2026',
                // Mot de passe de démonstration : « moodboard-demo ».
                'password_hash' => $hasher->hash('moodboard-demo'),
                'user_id' => 1,
                'destinataire_id' => 2,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
        ])->save();

        $liaisons = [];

        foreach ([1, 3, 4, 6] as $i => $photoId) {
            $liaisons[] = ['moodboard_id' => 1, 'photo_id' => $photoId, 'ordre' => $i, 'mise_en_avant' => $i === 0];
        }

        foreach ([2, 5] as $i => $photoId) {
            $liaisons[] = ['moodboard_id' => 2, 'photo_id' => $photoId, 'ordre' => $i, 'mise_en_avant' => false];
        }

        $this->table('moodboards_photos')->insert($liaisons)->save();
    }

    /**
     * Une galerie client livrée, protégée par mot de passe.
     *
     * @param string $maintenant Horodatage commun aux lignes insérées.
     * @param \Authentication\PasswordHasher\DefaultPasswordHasher $hasher Hasher des mots de passe.
     * @return void
     */
    protected function insererGalerie(string $maintenant, DefaultPasswordHasher $hasher): void
    {
        $this->table('galeries')->insert([
            [
                'id' => 1,
                'nom' => 'Shooting Studio Durand — mars 2026',
                'slug' => 'shooting-studio-durand-mars-2026',
                'description' => 'Sélectionnez vos 3 photos préférées.',
                'client_id' => 2,
                'share_token' => 'demo-galerie-studio-durand-2026',
                // Mot de passe de démonstration : « galerie-demo ».
                'password_hash' => $hasher->hash('galerie-demo'),
                'telechargement_actif' => true,
                'quota_favoris' => 3,
                'date_livraison' => date('Y-m-d'),
                'actif' => true,
                'created' => $maintenant,
                'modified' => $maintenant,
            ],
        ])->save();

        $liaisons = [];

        foreach ([1, 2, 3, 4, 5, 6] as $i => $photoId) {
            $liaisons[] = ['galerie_id' => 1, 'photo_id' => $photoId, 'ordre' => $i];
        }

        $this->table('galeries_photos')->insert($liaisons)->save();
    }
}
