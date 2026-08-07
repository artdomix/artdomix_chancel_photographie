/**
 * Front public — script unique, sans build.
 *
 * Écrit en JavaScript classique plutôt qu'en modules ES : les bibliothèques
 * arrivent par CDN et exposent des variables globales (`gsap`, `htmx`, `L`).
 * Aucun bundler n'est donc nécessaire, ce qui était l'objectif — le projet doit
 * pouvoir être développé et déployé sans Node.
 *
 * Le fichier est enveloppé dans une IIFE pour ne rien laisser fuiter dans la
 * portée globale au-delà de ce qui est voulu.
 */
(function () {
    'use strict';

    var reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var gsapDispo = typeof window.gsap !== 'undefined';

    if (gsapDispo) {
        if (window.ScrollTrigger) {
            window.gsap.registerPlugin(window.ScrollTrigger);
        }
        if (window.Flip) {
            window.gsap.registerPlugin(window.Flip);
        }
    }

    /**
     * Signale au CSS que le JS a pris la main. Tant que cette classe est absente,
     * les éléments animés restent visibles : une page reste lisible même si le
     * CDN ne répond pas.
     */
    document.documentElement.classList.add('js-pret');

    // ---------------------------------------------------------------- menu

    function initMenu() {
        var bascule = document.querySelector('[data-menu-bascule]');
        var panneau = document.querySelector('[data-menu-panneau]');

        if (!bascule || !panneau) {
            return;
        }

        function fermer() {
            panneau.hidden = true;
            bascule.setAttribute('aria-expanded', 'false');
        }

        bascule.addEventListener('click', function () {
            var ouvert = bascule.getAttribute('aria-expanded') === 'true';
            panneau.hidden = ouvert;
            bascule.setAttribute('aria-expanded', String(!ouvert));
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && bascule.getAttribute('aria-expanded') === 'true') {
                fermer();
                bascule.focus();
            }
        });

        window.matchMedia('(min-width: 768px)').addEventListener('change', fermer);
    }

    // ---------------------------------------------------------- animations

    function initAnimations(racine) {
        racine = racine || document;

        var cibles = racine.querySelectorAll('[data-anim]:not([data-anim-pret])');

        if (cibles.length === 0) {
            return;
        }

        // Marqué tout de suite : un échange htmx peut relancer l'init sur un
        // nœud déjà traité.
        Array.prototype.forEach.call(cibles, function (el) {
            el.setAttribute('data-anim-pret', '');
        });

        // Sans GSAP — CDN indisponible — ou si l'utilisateur refuse les
        // animations, on remet les éléments à leur état final plutôt que de les
        // laisser invisibles.
        if (!gsapDispo || reduit) {
            Array.prototype.forEach.call(cibles, function (el) {
                el.style.opacity = '1';
                el.style.transform = 'none';
            });

            return;
        }

        Array.prototype.forEach.call(cibles, function (element) {
            if (element.dataset.anim === 'grille') {
                animerGrille(element);
            } else {
                animerApparition(element);
            }
        });
    }

    function animerGrille(grille) {
        var items = grille.querySelectorAll('[data-anim-item]');

        if (items.length === 0) {
            return;
        }

        window.gsap.fromTo(items,
            { opacity: 0, y: 40 },
            {
                opacity: 1,
                y: 0,
                duration: 0.7,
                ease: 'power3.out',
                stagger: { amount: Math.min(0.6, items.length * 0.05) },
                scrollTrigger: { trigger: grille, start: 'top 85%', once: true },
            });
    }

    function animerApparition(element) {
        window.gsap.fromTo(element,
            { opacity: 0, y: 24 },
            {
                opacity: 1,
                y: 0,
                duration: 0.6,
                ease: 'power2.out',
                scrollTrigger: { trigger: element, start: 'top 90%', once: true },
            });
    }

    // ------------------------------------------------------------ lightbox

    var etatLightbox = null;

    function initLightbox(racine) {
        racine = racine || document;

        var items = racine.querySelectorAll('[data-lightbox]:not([data-lightbox-pret])');

        Array.prototype.forEach.call(items, function (item) {
            item.setAttribute('data-lightbox-pret', '');
            item.addEventListener('click', function (event) {
                event.preventDefault();
                ouvrirLightbox(item);
            });
        });

        if (!document.body.dataset.lightboxClavier) {
            document.body.dataset.lightboxClavier = '1';
            document.addEventListener('keydown', auClavierLightbox);
        }
    }

    function elementsGalerie(item) {
        var galerie = item.closest('[data-galerie]') || document;

        return Array.prototype.slice.call(galerie.querySelectorAll('[data-lightbox]'));
    }

    function ouvrirLightbox(item) {
        if (etatLightbox) {
            return;
        }

        var image = item.querySelector('img');

        if (!image) {
            return;
        }

        var conteneur = construireLightbox(item);
        var origine = image.parentElement;
        var marqueur = document.createComment('lightbox-origine');

        origine.insertBefore(marqueur, image);

        var avant = (gsapDispo && window.Flip) ? window.Flip.getState(image) : null;

        conteneur.querySelector('[data-lightbox-scene]').appendChild(image);
        document.body.appendChild(conteneur);
        document.body.style.overflow = 'hidden';

        etatLightbox = {
            conteneur: conteneur,
            image: image,
            marqueur: marqueur,
            item: item,
            elements: elementsGalerie(item),
        };

        // L'image plein écran doit rester nette : on force la variante la plus
        // grande, préparée par le serveur.
        if (image.dataset.lightboxSrc && image.src !== image.dataset.lightboxSrc) {
            image.src = image.dataset.lightboxSrc;
            image.removeAttribute('srcset');
            image.removeAttribute('sizes');
        }

        if (avant) {
            window.Flip.from(avant, {
                duration: reduit ? 0 : 0.55,
                ease: 'power3.inOut',
                absolute: true,
                scale: false,
            });
        }

        conteneur.querySelector('[data-lightbox-fermer]').focus();
    }

    function fermerLightbox() {
        if (!etatLightbox) {
            return;
        }

        var etat = etatLightbox;
        var avant = (gsapDispo && window.Flip) ? window.Flip.getState(etat.image) : null;

        etat.marqueur.parentElement.insertBefore(etat.image, etat.marqueur);
        etat.marqueur.remove();

        if (avant) {
            window.Flip.from(avant, {
                duration: reduit ? 0 : 0.45,
                ease: 'power3.inOut',
                absolute: true,
                scale: false,
                onComplete: function () {
                    etat.conteneur.remove();
                },
            });
        } else {
            etat.conteneur.remove();
        }

        document.body.style.overflow = '';
        etatLightbox = null;

        // Le focus revient sur la vignette d'où l'on vient, pas en haut de page.
        etat.item.focus({ preventScroll: true });
    }

    function naviguerLightbox(pas) {
        if (!etatLightbox) {
            return;
        }

        var elements = etatLightbox.elements;
        var index = elements.indexOf(etatLightbox.item);

        if (index === -1) {
            return;
        }

        var suivant = elements[(index + pas + elements.length) % elements.length];

        fermerLightbox();
        ouvrirLightbox(suivant);
    }

    function auClavierLightbox(event) {
        if (!etatLightbox) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            fermerLightbox();
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            naviguerLightbox(1);
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            naviguerLightbox(-1);
        } else if (event.key === 'Tab') {
            // Le focus reste enfermé dans la lightbox tant qu'elle est ouverte.
            piegerFocus(event);
        }
    }

    function piegerFocus(event) {
        var focusables = etatLightbox.conteneur.querySelectorAll('button');

        if (focusables.length === 0) {
            return;
        }

        var premier = focusables[0];
        var dernier = focusables[focusables.length - 1];

        if (event.shiftKey && document.activeElement === premier) {
            event.preventDefault();
            dernier.focus();
        } else if (!event.shiftKey && document.activeElement === dernier) {
            event.preventDefault();
            premier.focus();
        }
    }

    function construireLightbox(item) {
        var legende = item.dataset.lightboxLegende || '';
        var fiche = item.dataset.lightboxFiche || '';
        var conteneur = document.createElement('div');

        conteneur.className = 'fixed inset-0 z-50 flex items-center justify-center';
        conteneur.setAttribute('role', 'dialog');
        conteneur.setAttribute('aria-modal', 'true');
        conteneur.setAttribute('aria-label', legende || 'Photo en plein écran');
        conteneur.innerHTML = ''
            + '<div data-lightbox-fond class="absolute inset-0 bg-noir/95"></div>'
            + '<div data-lightbox-scene class="relative flex max-h-[88vh] max-w-[92vw] items-center justify-center"></div>'
            + '<p class="absolute bottom-6 left-1/2 -translate-x-1/2 px-4 text-center text-sm text-gris">'
            + escaper(legende)
            // Passage de la lightbox à la fiche : celle-ci porte le texte, les
            // métadonnées et l'adresse partageable. Un lien, et non un bouton :
            // il doit s'ouvrir dans un nouvel onglet au clic du milieu.
            + (fiche
                ? '<br><a class="lien-souligne text-corail" href="' + escaperAttribut(fiche)
                    + '">Voir la fiche</a>'
                : '')
            + '</p>'
            + '<button type="button" data-lightbox-precedent class="absolute left-4 top-1/2 -translate-y-1/2 p-3 text-blanc-casse hover:text-corail" aria-label="Photo précédente">&#8249;</button>'
            + '<button type="button" data-lightbox-suivant class="absolute right-4 top-1/2 -translate-y-1/2 p-3 text-blanc-casse hover:text-corail" aria-label="Photo suivante">&#8250;</button>'
            + '<button type="button" data-lightbox-fermer class="absolute right-4 top-4 p-3 text-blanc-casse hover:text-corail" aria-label="Fermer">&#10005;</button>';

        conteneur.querySelector('[data-lightbox-fond]').addEventListener('click', fermerLightbox);
        conteneur.querySelector('[data-lightbox-fermer]').addEventListener('click', fermerLightbox);
        conteneur.querySelector('[data-lightbox-precedent]').addEventListener('click', function () {
            naviguerLightbox(-1);
        });
        conteneur.querySelector('[data-lightbox-suivant]').addEventListener('click', function () {
            naviguerLightbox(1);
        });

        return conteneur;
    }

    /**
     * Échappe une valeur destinée à un attribut HTML.
     *
     * `escaper` passe par `textContent`, qui laisse intacts les guillemets : sans
     * distinction, une URL en contenant un sortirait de l'attribut.
     */
    function escaperAttribut(valeur) {
        return String(valeur).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function escaper(texte) {
        var div = document.createElement('div');
        div.textContent = texte;

        return div.innerHTML;
    }

    // --------------------------------------------------------------- carte

    function initCarte() {
        var conteneur = document.getElementById('carte');

        // Leaflet n'est chargé que sur la page carte : ailleurs, `L` est absent
        // et il n'y a rien à faire.
        if (!conteneur || typeof window.L === 'undefined') {
            return;
        }

        var points = [];

        try {
            points = JSON.parse(conteneur.dataset.carte || '[]');
        } catch (e) {
            return;
        }

        if (points.length === 0) {
            return;
        }

        var carte = window.L.map(conteneur, { scrollWheelZoom: false });

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 18,
        }).addTo(carte);

        var marqueurs = points.map(function (point) {
            return window.L.marker([point.lat, point.lng])
                .addTo(carte)
                .bindPopup('<a href="' + point.url + '"><img src="' + point.vignette
                    + '" alt="" width="160"><br>' + escaper(point.titre) + '</a>');
        });

        // Cadrage sur l'ensemble des points, avec une marge pour que les
        // marqueurs des bords ne soient pas collés au cadre.
        carte.fitBounds(window.L.featureGroup(marqueurs).getBounds(), { padding: [40, 40] });
    }

    // ----------------------------------------------------------- démarrage

    function demarrer() {
        initMenu();
        initCarte();
        initAnimations();
        initLightbox();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', demarrer);
    } else {
        demarrer();
    }

    // Après un échange htmx, seul le fragment remplacé est ré-animé : rejouer
    // l'ensemble ferait clignoter la page déjà en place.
    document.addEventListener('htmx:afterSwap', function (event) {
        initAnimations(event.detail.target);
        initLightbox(event.detail.target);
    });
})();
