/**
 * Back-office — script unique, sans build.
 *
 * Séparé du front : une page publique n'a aucune raison d'embarquer l'uploader
 * ni le réordonnancement. Comme `chancel.js`, ce fichier est un script classique
 * enveloppé dans une IIFE : les bibliothèques arrivent par CDN et exposent des
 * globales, aucun bundler n'intervient.
 */
(function () {
    'use strict';

    document.documentElement.classList.add('js-pret');

    // --------------------------------------------------------------- upload

    /**
     * Import de photos par glisser-déposer.
     *
     * Les fichiers sont envoyés **un par un, en série**. C'est délibéré : sur un
     * hébergement mutualisé, chaque photo mobilise GD pour produire douze
     * dérivés, et lancer vingt requêtes en parallèle ferait tomber le serveur
     * sur une limite de mémoire ou de processus. En série, chaque requête reste
     * courte et l'import d'un lot progresse de façon régulière et prévisible.
     */
    function initUpload() {
        var zone = document.querySelector('[data-upload]');

        if (!zone) {
            return;
        }

        var champ = zone.querySelector('[data-upload-input]');
        var progression = document.querySelector('[data-upload-progression]');
        var barre = document.querySelector('[data-upload-barre]');
        var etat = document.querySelector('[data-upload-etat]');
        var resultats = document.querySelector('[data-upload-resultats]');

        var url = zone.dataset.url;
        var csrf = zone.dataset.csrf;

        var enCours = false;

        zone.addEventListener('click', function () {
            champ.click();
        });

        champ.addEventListener('change', function () {
            envoyerTout(Array.prototype.slice.call(champ.files));
        });

        ['dragenter', 'dragover'].forEach(function (type) {
            zone.addEventListener(type, function (event) {
                event.preventDefault();
                zone.classList.add('border-corail');
            });
        });

        ['dragleave', 'drop'].forEach(function (type) {
            zone.addEventListener(type, function (event) {
                event.preventDefault();
                zone.classList.remove('border-corail');
            });
        });

        zone.addEventListener('drop', function (event) {
            var fichiers = event.dataTransfer ? Array.prototype.slice.call(event.dataTransfer.files) : [];

            envoyerTout(fichiers.filter(function (fichier) {
                return fichier.type.indexOf('image/') === 0;
            }));
        });

        function envoyerTout(fichiers) {
            if (enCours || fichiers.length === 0) {
                return;
            }

            enCours = true;
            progression.classList.remove('hidden');

            // Chaîne de promesses plutôt que `for … await` : le fichier suivant
            // ne part qu'une fois le précédent traité, ce qui est justement le
            // comportement recherché.
            var chaine = Promise.resolve();

            fichiers.forEach(function (fichier, index) {
                chaine = chaine.then(function () {
                    etat.textContent = 'Envoi de ' + fichier.name
                        + ' (' + (index + 1) + ' sur ' + fichiers.length + ')…';

                    return envoyer(fichier);
                }).then(function () {
                    barre.style.width = Math.round(((index + 1) / fichiers.length) * 100) + '%';
                });
            });

            chaine.then(function () {
                etat.textContent = fichiers.length + ' fichier(s) traité(s).';
                enCours = false;
                champ.value = '';
            });
        }

        function envoyer(fichier) {
            var donnees = new FormData();
            donnees.append('fichier', fichier);

            return fetch(url, {
                method: 'POST',
                body: donnees,
                headers: {
                    'X-CSRF-Token': csrf,
                    // Le serveur renvoie un fragment plutôt qu'une page entière.
                    'HX-Request': 'true',
                },
            }).then(function (reponse) {
                return reponse.text();
            }).then(function (html) {
                resultats.insertAdjacentHTML('beforeend', html);
            }).catch(function (erreur) {
                // Un échec réseau ne doit pas interrompre le lot : la photo
                // suivante a toutes les chances de passer.
                resultats.insertAdjacentHTML(
                    'beforeend',
                    '<li class="py-2 text-sm text-red-400">✕ ' + echapper(fichier.name)
                        + ' : ' + echapper(erreur.message) + '</li>',
                );
            });
        }
    }

    // ------------------------------------------------------------------ tri

    /**
     * Réordonnancement des photos d'un album à la souris.
     *
     * Utilise l'API HTML5 native de glisser-déposer plutôt qu'une bibliothèque :
     * le besoin se limite à réordonner une grille, et une dépendance de plus
     * serait à suivre à chaque montée de version pour un gain nul.
     *
     * Un contrôle clavier double le geste souris : sans lui, réordonner un album
     * serait impossible pour qui n'utilise pas de souris.
     */
    function initTri() {
        var grille = document.querySelector('[data-tri]');

        if (!grille) {
            return;
        }

        var url = grille.dataset.triUrl;
        var csrf = grille.dataset.csrf;
        var source = null;
        var minuteur;

        function items() {
            return Array.prototype.slice.call(grille.querySelectorAll('[data-tri-item]'));
        }

        items().forEach(function (item) {
            item.draggable = true;

            item.addEventListener('dragstart', function () {
                source = item;
                item.classList.add('opacity-40');
            });

            item.addEventListener('dragend', function () {
                item.classList.remove('opacity-40');
                enregistrer();
            });

            item.addEventListener('dragover', function (event) {
                event.preventDefault();

                if (!source || source === item) {
                    return;
                }

                var rect = item.getBoundingClientRect();
                var apres = event.clientX > rect.left + rect.width / 2;

                item.parentNode.insertBefore(source, apres ? item.nextSibling : item);
            });

            // Flèches gauche/droite : déplace l'élément focalisé d'un cran.
            item.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                    return;
                }

                event.preventDefault();

                var liste = items();
                var index = liste.indexOf(item);
                var cible = event.key === 'ArrowLeft' ? index - 1 : index + 1;

                if (cible < 0 || cible >= liste.length) {
                    return;
                }

                if (event.key === 'ArrowLeft') {
                    liste[cible].parentNode.insertBefore(item, liste[cible]);
                } else {
                    liste[cible].parentNode.insertBefore(item, liste[cible].nextSibling);
                }

                item.focus();
                enregistrer();
            });
        });

        function enregistrer() {
            // Regroupe les déplacements successifs : réordonner dix photos ne
            // doit pas produire dix requêtes.
            clearTimeout(minuteur);

            minuteur = setTimeout(function () {
                var ordre = items().map(function (item) {
                    return item.dataset.triItem;
                });

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrf,
                    },
                    body: JSON.stringify({ ordre: ordre }),
                }).then(function (reponse) {
                    var etat = grille.querySelector('[data-tri-etat]')
                        || document.querySelector('[data-tri-etat]');

                    if (etat) {
                        etat.textContent = reponse.ok
                            ? 'Ordre enregistré.'
                            : "L'ordre n'a pas pu être enregistré.";
                    }
                });
            }, 600);
        }
    }

    function echapper(texte) {
        var div = document.createElement('div');
        div.textContent = texte;

        return div.innerHTML;
    }

    // ----------------------------------------------------------- démarrage

    function demarrer() {
        initUpload();
        initTri();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', demarrer);
    } else {
        demarrer();
    }
})();
