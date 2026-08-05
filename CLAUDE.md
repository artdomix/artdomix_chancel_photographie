# CLAUDE.md

Guide pour Claude Code (et tout autre agent) travaillant sur ce dépôt.

## Le projet

Site vitrine/portfolio du photographe **Chancel** (https://chancel.art-domix.fr) :
galeries photo, portfolio, blog, vidéos, livres, tirages, expositions, moodboards
partageables, formulaire de contact, back-office d'administration et espace membre.

**Réécriture complète.** L'ancien site (CakePHP 3.9, figé en mars 2021, hébergé sur
`gitlab.com/artdomix/artdomix_chancel_photographie`) n'est pas migré : on repart d'un
projet neuf. Le domaine métier et le vocabulaire français sont conservés, l'architecture
technique non. Se référer à l'ancien dépôt uniquement pour comprendre une intention
métier, jamais comme modèle de code.

## Stack

| Élément | Version / choix |
|---|---|
| Framework | CakePHP **5.4** (PHP **8.2+** requis) |
| Base de données | **MySQL / MariaDB**, InnoDB, `utf8mb4` / `utf8mb4_unicode_ci` |
| Templates | `.php` dans `templates/` |
| CSS | **Tailwind 4** — configuration CSS-first (`@theme`), il n'y a pas de `tailwind.config.js` |
| Interactivité | **HTMX 2** (fragments serveur, pas de SPA) |
| Animation | **GSAP 3.15** — ScrollTrigger, Flip, SplitText, ScrollSmoother (tous gratuits depuis avril 2025) |
| Build | **Vite 7** — sortie dans `webroot/build/` |
| Carte | Leaflet + OpenStreetMap (pas de clé API) |
| Serveur | Apache mutualisé + `mod_rewrite` |

Plugins Composer : `cakephp/authentication`, `cakephp/authorization`,
`dereuromark/cakephp-captcha` (formulaire de contact), `cakephp/migrations`,
`intervention/image` (dérivés).

## Mise en route

```bash
composer install
npm install
cp config/app_local.example.php config/app_local.php   # puis renseigner la BDD + le salt

mysql -e "CREATE DATABASE chancel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE DATABASE chancel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

npm run build          # OBLIGATOIRE : sans lui, aucune page n'a de style
bin/cake migrations migrate
bin/cake migrations seed
bin/cake server        # http://localhost:8765
```

Vérifications : `vendor/bin/phpunit`, `vendor/bin/phpcs`, `php -l` sur les fichiers
touchés.

## Contraintes structurantes

Trois contraintes expliquent la plupart des choix de ce dépôt. Les enfreindre casse
la production.

### 1. L'hébergement est mutualisé et n'a pas Node

`webroot/build/` est **volontairement committé** — c'est l'exception au réflexe « ne pas
versionner les artefacts de build ». `npm run build` tourne en local ou en CI, jamais
sur le serveur. **Toute modification dans `resources/` doit être suivie d'un
`npm run build` et le résultat committé**, sinon la prod sert d'anciens assets.

### 2. La base est MySQL, y compris pour les tests

Pas de SQLite, même en test : le schéma utilise des `ENUM` et des index `FULLTEXT`.
La connexion `test` de `app_local.example.php` pointe donc sur MySQL — le squelette
CakePHP la fait pointer sur SQLite par défaut, ne pas rétablir ce défaut.

On développe sur MariaDB mais la prod est probablement MySQL 8 : s'en tenir au
dénominateur commun. `utf8mb4_unicode_ci` et **jamais** `utf8mb4_0900_ai_ci`
(MySQL 8 uniquement). Pas de colonnes JSON, pas de fonction propre à une version.

### 3. Le dépôt ne contient aucune image

L'ancien dépôt pesait 1,3 Go parce que 550 Mo de JPEG y étaient commités. Ici :
originaux dans `storage/originaux/` (hors `webroot/`, gitignoré), dérivés dans
`webroot/media/photos/` (gitignoré). **Ne jamais committer d'image ni d'archive.**

## Architecture

```
config/            routes.php, migrations/, seeds/, app_local.php (gitignoré)
resources/css|js/  sources Tailwind + JS — c'est ici qu'on édite le front
src/
  Controller/            front public
  Controller/Admin/      préfixe admin
  Controller/Membre/     préfixe membre
  Service/               traitement d'images, EXIF, tokens de partage
  View/Helper/           ViteHelper, PhotoHelper, SeoHelper
templates/         vues .php
webroot/build/     sortie Vite — COMMITTÉE (cf. contrainte 1)
webroot/media/     dérivés générés — gitignoré
storage/           originaux — gitignoré
```

### Sécurité : refus par défaut

L'ancien site ouvrait tout (`$this->Auth->allow()`) puis refermait sur le préfixe
`admin` — un oubli suffisait à exposer une page. Ici, `AuthenticationMiddleware` et
`AuthorizationMiddleware` **refusent par défaut** ; chaque action publique est
autorisée explicitement dans son contrôleur. Une action nouvelle est inaccessible
tant qu'on ne l'a pas ouverte — c'est voulu, ne pas « corriger » ce comportement en
rouvrant globalement.

Les accès par lien (`/m/{token}` moodboards, `/g/{token}` galeries client) reposent sur
un token opaque et un mot de passe hashé, sans compte utilisateur.

### Modèle de données

- `photos` ↔ `albums` en **N-N** (`albums_photos`, avec `ordre`), et `photos` ↔ `tags`
  en **N-N** (`photos_tags`). Une photo appartient à plusieurs albums : c'est le
  changement majeur par rapport à l'ancien schéma.
- `albums` forme un **arbre** via le `TreeBehavior` du cœur CakePHP : `parent_id` +
  `lft`/`rght`. Il n'y a **pas** de colonne `child_id` — les enfants sont déduits du
  jeu de nœuds imbriqués. Toujours passer par les méthodes du behavior
  (`moveUp`, `moveDown`, `find('threaded')`), jamais écrire `lft`/`rght` à la main.
- `categories` et `typecategories` de l'ancien site **n'existent plus**, remplacés par
  l'arbre d'albums et les tags.
- `exifs` (1-1 avec `photos`) porte les GPS utilisés par la carte.
- Clés étrangères réelles avec `ON DELETE` explicite — l'ancien schéma n'en avait aucune.

### Pipeline images

`src/Service/Image/` génère à l'upload quatre variantes (`thumb` 320, `grid` 640,
`content` 1200, `large` 2048) en AVIF, WebP et JPEG, plus un `lqip` inline. Règles :

- **L'original n'est jamais servi** — il reste hors `webroot/`.
- **AVIF conditionnel** : `function_exists('imageavif')` peut être faux sur le
  mutualisé ; le flag `has_avif` par photo évite de référencer un fichier absent.
- Génération **synchrone**, une photo par requête HTTP : pas de worker sur mutualisé.
- `unlink()` toujours précédé de `file_exists()`.
- Upload validé : type MIME réel, extension, taille et dimensions maximales.

### GSAP et accessibilité

Toutes les animations passent par `gsap.matchMedia()` avec une branche
`prefers-reduced-motion: reduce` qui **remet les éléments à leur état final** — sans
elle, ils resteraient à l'opacité 0 posée par le CSS. Ne jamais ajouter d'animation
hors de ce garde-fou.

Le CSS ne masque les éléments `[data-anim]` qu'une fois la classe `js-pret` posée par
le JS : une page reste lisible si le bundle ne se charge pas.

## Conventions du code

- **Domaine en français, framework en anglais.** Tables et colonnes en français
  (`photos.titre`, `albums.nom`, `actif`, `date_capture`), entités métier françaises
  (`Livres`, `Tirages`, `Modeles`, `Moodboards`, `Galeries`). Ne pas angliciser.
- Commentaires, messages de validation et messages de commit **en français**.
- Les commentaires expliquent *pourquoi*, pas *quoi*.
- `declare(strict_types=1);` en tête de chaque fichier PHP.
- Suivre `.editorconfig` et le standard CakePHP (`vendor/bin/phpcs`).

## Règles de travail

- **Ne jamais committer `config/app_local.php`** (identifiants BDD, salt) — gitignoré,
  garder cet état.
- Ne jamais committer d'image, de dump SQL ni d'archive binaire.
- Après toute modification de `resources/`, relancer `npm run build` et committer
  `webroot/build/`.
- Décrire précisément ce qui n'a pas pu être vérifié : la version exacte du MySQL de
  prod, la présence d'AVIF dans le GD de l'hébergeur, la réécriture Apache et l'envoi
  SMTP réel ne sont pas testables ici.
