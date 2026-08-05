# Chancel Photographie

Site vitrine et portfolio du photographe **Chancel** — https://chancel.art-domix.fr

Galeries photo, portfolio arborescent, blog, moodboards partageables, galeries
client, back-office d'administration et espace membre.

Réécriture complète du site précédent (CakePHP 3.9, figé en 2021). Voir
[`CLAUDE.md`](CLAUDE.md) pour les conventions et les contraintes du projet.

## Stack

| Élément | Version |
|---|---|
| CakePHP | 5.4 (PHP 8.2+) |
| Base de données | MySQL / MariaDB, InnoDB, `utf8mb4_unicode_ci` |
| CSS | Tailwind 4 (configuration CSS-first, pas de `tailwind.config.js`) |
| Interactivité | HTMX 2 |
| Animation | GSAP 3.15 (ScrollTrigger, Flip, SplitText) |
| Build | Vite 7 |
| Carte | Leaflet + OpenStreetMap |

## Installation

```bash
composer install
npm install

cp config/app_local.example.php config/app_local.php
# renseigner Datasources + Security.salt

mysql -e "CREATE DATABASE chancel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE DATABASE chancel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

npm run build                          # OBLIGATOIRE : sans lui, aucune page n'a de style
bin/cake migrations migrate
bin/cake migrations migrate -p Captcha
bin/cake seeds run ComptesSeed         # comptes d'accès (admin + membre)
bin/cake seeds run DemoSeed            # jeu de démonstration, jamais en production
bin/cake generer_images_demo           # visuels de remplacement pour le jeu de démo

bin/cake server                        # http://localhost:8765
```

### Comptes

`ComptesSeed` crée les deux comptes réels. Il est rejouable : le relancer
réinitialise le mot de passe si celui-ci a été oublié en développement.

| Rôle | Identifiant | Mot de passe initial |
|---|---|---|
| Administrateur | `dodo15@msn.com` | `artdomix` |
| Membre | `Celine.benner@gmail.com` | `artdomix` |

**Ce mot de passe est en clair dans un fichier versionné et ne fait que
8 caractères**, là où le formulaire de changement en exige 12. Il est prévu pour
la première connexion : à changer dès la mise en ligne, depuis
« Mot de passe oublié » qui appliquera la règle des 12 caractères.

`DemoSeed` crée en plus deux comptes de démonstration
(`admin@chancel.test` / `admin-demo-2026` et `client@chancel.test` /
`client-demo-2026`) — **à supprimer avant toute mise en ligne**.

## Vérifications

```bash
vendor/bin/phpunit     # tests
vendor/bin/phpcs       # standard de code CakePHP
vendor/bin/phpcbf      # correction automatique
```

Les tests tournent sur **MySQL**, jamais sur SQLite : le schéma utilise des
`ENUM` et des index `FULLTEXT`, des tests qui passeraient sur un autre moteur ne
prouveraient rien.

## Déploiement sur hébergement mutualisé

L'hébergement cible est un Apache mutualisé **sans Node ni Composer**. Le
déploiement consiste donc à envoyer un projet déjà construit.

### 1. Préparer en local

```bash
composer install --no-dev --optimize-autoloader
npm run build
```

`webroot/build/` est **volontairement versionné** — c'est l'exception au réflexe
« ne pas committer les artefacts de build ». Toute modification dans
`resources/` doit être suivie d'un `npm run build` et du commit du résultat,
faute de quoi la production sert d'anciens assets.

### 2. Envoyer

À transférer : tout le projet **sauf** `node_modules/`, `tests/`, `.git/`.
À créer sur le serveur, en écriture pour PHP : `tmp/`, `logs/`,
`webroot/media/photos/`, `storage/originaux/`.

`storage/` doit rester **hors de la racine web** : il contient les originaux,
qui ne sont jamais servis.

### 3. Configurer

```php
// config/app_local.php
'debug' => false,
'Security' => ['salt' => '…'],            // 64 caractères aléatoires, propres au serveur
'App' => ['fullBaseUrl' => 'https://chancel.art-domix.fr'],
```

Puis appliquer le schéma :

```bash
bin/cake migrations migrate
bin/cake migrations migrate -p Captcha
```

Si la ligne de commande n'est pas disponible, exporter le schéma depuis un
environnement local (`mysqldump --no-data`) et l'importer via phpMyAdmin.

### 4. Créer les comptes

```bash
bin/cake seeds run ComptesSeed
```

Puis se connecter et **changer immédiatement les deux mots de passe** : celui du
seed est en clair dans le dépôt.

Pour ajouter un compte supplémentaire :

```bash
bin/cake console
```

```php
$users = \Cake\ORM\TableRegistry::getTableLocator()->get('Users');
$u = $users->newEntity(['email' => 'vous@exemple.fr', 'password' => 'phrase de passe longue']);
$u->role = 'admin';   // `role` n'est pas assignable en masse, volontairement
$users->saveOrFail($u);
```

### Checklist de mise en ligne

- [ ] `debug` à `false`
- [ ] `Security.salt` régénéré, différent du développement
- [ ] `App.fullBaseUrl` renseigné — requis par `HostHeaderMiddleware`
- [ ] comptes de démonstration (`*@chancel.test`) supprimés
- [ ] mots de passe des comptes de `ComptesSeed` changés depuis leur valeur initiale
- [ ] `config/app_local.php` **absent du dépôt** (il est gitignoré)
- [ ] `tmp/`, `logs/`, `webroot/media/photos/`, `storage/originaux/` inscriptibles
- [ ] HTTPS actif et redirection vérifiée (`.htaccess` racine)
- [ ] envoi SMTP testé depuis le formulaire de contact
- [ ] AVIF vérifié : `php -r 'var_dump(function_exists("imageavif"));'` — s'il
      manque, le site retombe seul sur WebP et JPEG, rien à modifier
- [ ] `/sitemap.xml` et `/robots.txt` répondent sur le domaine de production

## Dépannage

### « Missing or invalid CSRF cookie »

Le jeton CSRF est **signé avec `Security.salt`**. Changer le salt invalide donc
d'un coup tous les cookies déjà posés dans les navigateurs — et comme
`config/app_local.php` n'est pas versionné, chaque installation génère le sien.

C'est la cause la plus fréquente de cette erreur. Trois déclencheurs typiques :

- première installation après un clone, avec un onglet déjà ouvert ;
- `Security.salt` régénéré ou `app_local.php` recopié ;
- onglet resté ouvert très longtemps.

**Solution : vider les cookies du site**, ou ouvrir une fenêtre de navigation
privée. Depuis la correction, le site n'affiche plus une page 403 dans ce cas
mais renvoie au formulaire avec un message explicite.

Si l'erreur persiste avec des cookies neufs, vérifier que `Security.salt` est
bien identique entre les processus PHP servant le site (un seul
`config/app_local.php`, pas de variable d'environnement `SECURITY_SALT`
concurrente).

### Boucle de redirection sur un hébergement mutualisé

Sur la plupart des mutualisés, TLS est terminé par un répartiteur en amont :
Apache reçoit du HTTP en clair et `%{HTTPS}` vaut `off` même quand le visiteur
est bien en HTTPS. Le `.htaccess` tient compte de `X-Forwarded-Proto`,
`X-Forwarded-SSL` et du port 443 avant de rediriger, précisément pour éviter
cette boucle. Si l'hébergeur utilise un autre en-tête, l'ajouter aux conditions.

Penser aussi à renseigner `App.fullBaseUrl` en `https://…` : c'est lui qui
détermine les URL absolues (plan de site, e-mails, liens de partage).

## Points non vérifiables hors production

Quatre éléments n'ont pas pu être testés pendant le développement et sont à
contrôler au premier déploiement :

1. la version exacte de MySQL de l'hébergeur (le code s'en tient au
   dénominateur commun MySQL 5.7 / 8 / MariaDB) ;
2. la présence de l'AVIF dans le GD du serveur ;
3. la réécriture d'URL Apache et la redirection HTTPS ;
4. l'envoi SMTP réel.

## Licence

Code sous licence MIT. **Les photographies ne le sont pas** : tous droits
réservés à Chancel.
