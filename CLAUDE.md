# CLAUDE.md

Guide pour Claude Code (et tout autre agent) travaillant sur ce dépôt.

## Le projet

Site vitrine/portfolio du photographe **Chancel** (https://chancel.art-domix.fr) :
galeries photo, portfolio par catégories, blog, vidéos, livres, tirages,
expositions, formulaire de contact, plus un back-office complet d'administration.

Le code source de référence est historiquement sur GitLab
(`gitlab.com/artdomix/artdomix_chancel_photographie`, branche `master`).
Dernier commit fonctionnel : mars 2021 (« upgrade to cakephp 3.9.4 »).

## Stack

| Élément | Version / choix |
|---|---|
| Framework | CakePHP **3.9** (verrouillé en 3.9.8 dans `composer.lock`) |
| PHP | `composer.json` déclare `>=5.5.9` — **obsolète** : `src/Application.php` utilise les types de retour `: void`, donc PHP **7.1 minimum** est réellement requis |
| Base de données | MySQL, toutes les tables préfixées `art_` |
| Templates | `.ctp` (convention CakePHP 3, `src/Template/`) |
| Front | Bootstrap 2/3 + jQuery 2.1.3, thème HTML acheté (`webroot/*/template/`), flexslider, kwicks, superfish, touchTouch |
| Éditeur riche | plugin local `plugins/TinyMCE` (versionné dans le dépôt) |
| Serveur | Apache + `mod_rewrite` (double `.htaccess` : racine → `webroot/`, force HTTPS et non-www) |

Dépendances Composer notables : `dereuromark/cakephp-tools` (behavior `Slugged`),
`markstory/asset_compress`, `mobiledetect/mobiledetectlib` (détecteurs `mobile`/`tablet`
enregistrés dans `config/bootstrap.php`), `cakephp/migrations`, `muffin/slug` et
`admad/cakephp-glide` (**déclarés mais jamais utilisés dans le code** — candidats à la suppression).

## Mise en route

```bash
composer install                 # vendor/ n'est pas versionné
cp config/app.default.php config/app.php   # app.php est gitignoré (secrets)
# éditer config/app.php : Datasources.default, Security.salt, EmailTransport
```

Il n'y a **pas de migrations exploitables** : le schéma vit dans
`config/schema/artdomix_db*.sql.zip` (archives **corrompues/tronquées**, elles ne
se dézippent pas proprement — il faut récupérer un dump depuis la prod).
`config/schema/i18n.sql` et `sessions.sql` sont des tables utilitaires CakePHP.

Commandes utiles :

```bash
bin/cake server                  # serveur de dev
bin/cake bake                    # génération de code (bake est en require-dev)
bin/cake migrations migrate      # plugin chargé en CLI uniquement
composer update                  # cf. README, workflow historique WAMP
```

Aucune suite de tests n'existe : `phpunit.xml.dist` pointe vers `./tests/` qui est
absent du dépôt, et `.travis.yml` cible PHP 5.5–7.1 sur Travis CI (service arrêté).
**Ne pas prétendre qu'une modification est « testée »** — il n'y a rien à exécuter.
Le seul filet de sécurité disponible est `php -l` (lint) sur les fichiers touchés.

## Architecture

### Deux zones, un préfixe

- **Front public** : `src/Controller/*.php`, templates `src/Template/<Controller>/`.
- **Back-office** : préfixe de route `admin` → `src/Controller/Admin/*.php`,
  templates `src/Template/Admin/<Controller>/`, layout `Admin/Layout/default.ctp`.

L'authentification est gérée dans `src/Controller/AppController.php` :
`beforeFilter()` fait `$this->Auth->allow()` (tout ouvert) puis `deny()` **si et
seulement si** `prefix == 'admin'`. Le composant `Auth` restreint le login au scope
`Users.role_id = 1`. Toute nouvelle zone protégée doit passer par le préfixe `admin`,
sinon elle sera publique.

### Routes principales (`config/routes.php`)

| URL | Cible |
|---|---|
| `/` | `Pages::index` (page d'accueil, agrège photos/albums/articles/catégories) |
| `/portfolio/*` | `Typecategories::index` |
| `/portfolio/view/<slug>` | `Typecategories::view` |
| `/contact` | `Messages::contact` |
| `/admin` | `Users::login` |
| `/admin/*` | fallback `DashedRoute` sur `src/Controller/Admin/` |

Le reste passe par `$routes->fallbacks(DashedRoute::class)`.

### Modèle de données

22 tables, toutes préfixées `art_` via `$this->setTable('art_'.$this->getTable())`
dans chaque `initialize()`. Entités principales :

- `Photos` — le cœur. `belongsTo` Categories + Albums, `hasOne` Exifs,
  `hasMany` Articles/Modeles/Shootings/Tirages.
- `Typecategories` → `Categories` → `Albums` → `Photos` : la hiérarchie du portfolio.
  Les catégories sont filtrées par `actif != 0` et `album_id IS NOT NULL`.
- Contenu éditorial : `Articles` (blog) + `Commentaires`, `Pages`, `Livres`,
  `Expositions`, `Tirages`/`Typetirages`, `Videos`/`Typevideos`, `Modeles`, `Shootings`.
- Divers : `Users`/`Roles`, `Messages`/`Demandes` (contact), `Mails`, `Config`, `Exifs`.

Les URLs lisibles viennent du behavior `Tools.Slugged` (Articles, Livres, Shootings,
Typecategories, Videos), consommé via `findBySlug($slug)`.

### ⚠️ `defaultConnectionName()` dupliqué dans les 22 tables

Chaque `src/Model/Table/*Table.php` contient :

```php
public static function defaultConnectionName() {
    if($_SERVER['SERVER_NAME'] != 'localhost'){ return 'default'; }
    return 'localhost';
}
```

Conséquences : deux datasources (`default` et `localhost`) doivent exister dans
`config/app.php`, et **le code plante en CLI** (`$_SERVER['SERVER_NAME']` non défini).
Si tu touches à ce point, modifie les 22 fichiers de façon cohérente — ou mieux,
remonte la logique dans un `AppTable` commun / la config.

### Pipeline images — convention critique

`src/Controller/Component/ResizeImgComponent.php` génère, à l'upload, une déclinaison
par dossier numéroté sous `webroot/img/photos/` :

| Dossier | Largeur max | Usage |
|---|---|---|
| `1/` | original uploadé | **gitignoré** (`.gitignore`) |
| `2/` | 2048 px | grand format |
| `3/` | 500 px | vignette moyenne |
| `4/` | 120 px | miniature |
| `5/` | 940 px | pleine largeur contenu |
| `6/` | 270 px | grille |

Le nom de fichier stocké en base (`photos.url`) est `md5(nom_sans_extension).jpeg`,
avec un suffixe incrémental en cas de collision. Le ratio est préservé
(paysage/portrait détecté par comparaison largeur/hauteur).
`Admin/PhotosController::delete()` supprime en boucle sur les dossiers `2..6`.

**Environ 550 Mo de dérivés JPEG sont commités dans le dépôt** (3175 fichiers,
dossiers `2` à `6`) — c'est la raison du poids de 1,3 Go au clone. Ne pas ajouter
de nouvelles images binaires ; envisager Git LFS ou un stockage externe.

Les EXIF sont lus avec `exif_read_data()` et écrits dans `art_exifs`
(`date_capture` sert au tri chronologique des galeries).

### Composants

- `ImageToolComponent` (1400 lignes) — boîte à outils image, largement inutilisée.
- `ResizeImgComponent` — voir ci-dessus, chargé à la demande dans `Admin/PhotosController::add()`.
- `Galerie500pxComponent` — intégration 500px, **désactivée** (appel commenté dans `PagesController::index()`).

### Helpers

`src/View/Helper/` : `DateHelper` (formats FR), `VideoHelper` (embeds), `GravatarHelper`.

## Conventions du code existant

- **Domaine en français, framework en anglais** : classes/tables `Photos`, `Albums`,
  mais aussi `Commentaires`, `Livres`, `Tirages`, `Modeles`, `Demandes`, `Typecategories`.
  Colonnes en français (`nom`, `actif`, `date_capture`). Garder cette convention —
  ne pas « angliciser » au passage.
- Commentaires et messages de commit en français.
- Indentation mixte (tabulations dans le code métier, espaces dans le squelette CakePHP).
  `.editorconfig` existe : suivre le style du fichier édité plutôt qu'imposer un reformatage.
- Les templates `voir.ctp` / `liste.ctp` / `recherche.ctp` sont les variantes françaises
  ajoutées au-dessus des `view.ctp` / `index.ctp` générés par bake.

## Dette technique connue (à traiter lors de la mise à jour)

Rien de tout cela n'est un bug à corriger « en passant » sans demander — mais il faut
en tenir compte avant toute montée de version.

1. **`echo` / `print_r` / `var_dump` de debug en production** dans
   `Admin/PhotosController::add()` (~11 occurrences dans `src/Controller`).
   Ils s'affichent avant les redirections et polluent la sortie HTML.
2. **Clé API bit.ly en dur** dans `AppController::short_url()` (login `dodo15` +
   `apiKey`). À révoquer et sortir du code — la méthode n'est d'ailleurs appelée nulle part.
3. **Upload sans validation** : `Admin/PhotosController::add()` fait confiance à
   `$_FILES` (pas de contrôle de type MIME, d'extension ni de taille) et écrit
   directement dans `webroot/`.
4. **`isAuthorized()` compare `$user['role_id'] === '1'`** (identité stricte avec une
   *string*) alors que la colonne est entière → renvoie toujours `false`.
   Un commit précédent (« correction sql string vers int ») a traité un cas voisin.
5. **API dépréciées bloquantes pour CakePHP 4** :
   - 65 usages de `$this->request->data` → `getData()`
   - 87 imports de `Cake\Network\*` → `Cake\Http\*`
   - `$this->request->params[...]` en accès tableau → `getParam()`
   - `Router::parse()` (supprimé en 4.x) dans `Admin/PhotosController::delete()`
   - `TableRegistry::get()` → `TableRegistry::getTableLocator()->get()`
   - templates `.ctp` → `.php` en CakePHP 4
6. **`unlink()` sans `file_exists()`** dans `Admin/PhotosController::delete()` →
   warning PHP si une déclinaison manque.
7. `composer.json` déclare `"minimum-stability": "beta"` et un `php` minimum faux.
8. `config/asset_compress.ini` contient encore `baseUrl = http://cdn.example.com`
   (valeur d'exemple).
9. Archives SQL du schéma corrompues (cf. plus haut).

## Pistes de mise à jour (issues du README d'origine)

- Modulo 4 sur le numéro de semaine pour faire varier les galeries affichées.
- Boutique e-commerce pour les tirages.
- Nouvelle page d'accueil.
- Sécuriser les liens d'images via une librairie dédiée (le `admad/cakephp-glide`
  déjà présent dans `composer.json` était prévu pour ça).

## Règles de travail sur ce dépôt

- **Ne jamais commiter `config/app.php`** (identifiants BDD, `Security.salt`, SMTP) —
  il est gitignoré, garder cet état.
- Ne pas ajouter d'images ou d'archives binaires ; le dépôt est déjà surdimensionné.
- `vendor/`, `tmp/`, `logs/` sont ignorés — ne pas les versionner.
- Pas de CI active : vérifier manuellement (`php -l`) et décrire précisément ce qui
  n'a pas pu être vérifié.
- Le dépôt d'origine est GitLab ; ce dépôt GitHub peut être partiel. Vérifier
  l'historique avant de supposer qu'un fichier manque.
