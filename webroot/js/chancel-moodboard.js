/**
 * Thèmes d'animation des moodboards.
 *
 * Les quatre thèmes tiennent dans un seul fichier. Sans bundler il n'y a plus de
 * découpage automatique, et charger quatre fichiers séparés pour n'en exécuter
 * qu'un coûterait plus en requêtes que ce que l'ensemble pèse (quelques Ko).
 * Le thème effectivement appliqué vient de `data-theme`, posé par le serveur
 * d'après la colonne `moodboards.theme`.
 *
 * Le fichier s'initialise seul plutôt que d'être appelé par `chancel.js` : les
 * deux scripts sont en `defer` et rien ne garantit lequel s'exécute en premier.
 */
(function () {
    'use strict';

    var THEMES = {
        'mosaique-flip': mosaiqueFlip,
        'mur-parallaxe': murParallaxe,
        'carrousel-pinne': carrouselPinne,
        'grille-cinetique': grilleCinetique,
    };

    /**
     * Mosaïque animée par GSAP Flip.
     *
     * Les vignettes arrivent groupées au centre puis se déploient vers leur
     * position définitive. Flip mesure la position de départ et celle d'arrivée
     * et anime l'écart : la mise en page reste celle du CSS, aucune coordonnée
     * n'est calculée à la main.
     */
    function mosaiqueFlip(scene, items) {
        var gsap = window.gsap;

        // Sans le plugin Flip — CDN partiellement indisponible — on retombe sur
        // une révélation simple plutôt que de laisser la sélection invisible.
        if (!window.Flip) {
            return grilleCinetique(scene, items);
        }

        var etat = window.Flip.getState(items);

        // État de départ : tout est empilé au centre, réduit et légèrement tourné.
        gsap.set(items, {
            opacity: 0,
            scale: 0.6,
            xPercent: function (i) {
                return i % 2 === 0 ? -18 : 18;
            },
            yPercent: 12,
            rotation: function (i) {
                return (i % 3 - 1) * 4;
            },
        });

        var timeline = window.Flip.from(etat, {
            duration: 1.1,
            ease: 'power3.out',
            stagger: { amount: 0.5, from: 'center' },
            absolute: false,
            paused: true,
            onStart: function () {
                gsap.to(items, { opacity: 1, duration: 0.5, stagger: { amount: 0.5 } });
            },
        });

        var declencheur = window.ScrollTrigger.create({
            trigger: scene,
            start: 'top 80%',
            once: true,
            onEnter: function () {
                timeline.play();
            },
        });

        // Survol : la vignette passe au premier plan sans bousculer ses voisines.
        var survols = [];

        Array.prototype.forEach.call(items, function (item) {
            var entrer = function () {
                gsap.to(item, { scale: 1.04, zIndex: 10, duration: 0.4, ease: 'power2.out' });
            };
            var sortir = function () {
                gsap.to(item, { scale: 1, zIndex: 1, duration: 0.4, ease: 'power2.out' });
            };

            item.addEventListener('mouseenter', entrer);
            item.addEventListener('mouseleave', sortir);

            survols.push(function () {
                item.removeEventListener('mouseenter', entrer);
                item.removeEventListener('mouseleave', sortir);
            });
        });

        return function () {
            declencheur.kill();
            timeline.kill();
            survols.forEach(function (retirer) {
                retirer();
            });
        };
    }

    /**
     * Mur en parallaxe : les colonnes défilent à des vitesses différentes.
     *
     * L'effet est piloté par le scroll plutôt que par une durée, pour que le
     * visiteur garde le contrôle du rythme — une animation autonome sur un mur
     * de photos donne vite le tournis.
     */
    function murParallaxe(scene, items) {
        var gsap = window.gsap;
        var colonnes = scene.querySelectorAll('[data-moodboard-colonne]');
        var declencheurs = [];

        gsap.set(items, { opacity: 1 });

        Array.prototype.forEach.call(colonnes, function (colonne, index) {
            // Les colonnes paires montent, les impaires descendent : le décalage
            // crée la profondeur sans que rien ne sorte du cadre.
            var amplitude = index % 2 === 0 ? -80 : 60;

            declencheurs.push(gsap.to(colonne, {
                y: amplitude,
                ease: 'none',
                scrollTrigger: {
                    trigger: scene,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.6,
                },
            }).scrollTrigger);
        });

        var apparition = gsap.fromTo(items,
            { opacity: 0, y: 40 },
            {
                opacity: 1,
                y: 0,
                duration: 0.9,
                ease: 'power3.out',
                stagger: { amount: 0.8, from: 'start' },
                scrollTrigger: { trigger: scene, start: 'top 85%', once: true },
            });

        return function () {
            declencheurs.forEach(function (declencheur) {
                if (declencheur) {
                    declencheur.kill();
                }
            });

            if (apparition.scrollTrigger) {
                apparition.scrollTrigger.kill();
            }

            apparition.kill();
        };
    }

    /**
     * Carrousel horizontal épinglé : la section reste fixe pendant que les
     * photos défilent latéralement, au rythme du scroll vertical.
     *
     * Volontairement désactivé sous 768 px : sur un écran étroit, immobiliser la
     * page pour imposer un défilement horizontal est plus déroutant qu'élégant,
     * et cela casse le geste de scroll attendu sur mobile.
     */
    function carrouselPinne(scene, items) {
        var gsap = window.gsap;
        var piste = scene.querySelector('[data-moodboard-piste]');

        gsap.set(items, { opacity: 1 });

        if (!piste || window.innerWidth < 768) {
            // Repli : simple révélation en cascade, la piste défile nativement.
            var simple = gsap.fromTo(items,
                { opacity: 0, x: 40 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 0.7,
                    ease: 'power2.out',
                    stagger: 0.08,
                    scrollTrigger: { trigger: scene, start: 'top 85%', once: true },
                });

            return function () {
                if (simple.scrollTrigger) {
                    simple.scrollTrigger.kill();
                }

                simple.kill();
            };
        }

        function distance() {
            return piste.scrollWidth - scene.offsetWidth;
        }

        var animation = gsap.to(piste, {
            x: function () {
                return -distance();
            },
            ease: 'none',
            scrollTrigger: {
                trigger: scene,
                start: 'top top',
                // La hauteur de scroll épinglée correspond à la largeur à
                // parcourir : le défilement horizontal avance donc au même
                // rythme que le vertical.
                end: function () {
                    return '+=' + distance();
                },
                pin: true,
                scrub: 0.8,
                anticipatePin: 1,
                invalidateOnRefresh: true,
            },
        });

        return function () {
            if (animation.scrollTrigger) {
                animation.scrollTrigger.kill();
            }

            animation.kill();
        };
    }

    /**
     * Grille cinétique : les vignettes se révèlent par un masque qui s'ouvre et
     * réagissent légèrement à la position de la souris.
     *
     * La réaction au pointeur passe par `quickTo`, qui réutilise la même
     * animation au lieu d'en créer une à chaque `mousemove` — sans quoi le
     * ramasse-miettes travaillerait en continu pendant tout le survol.
     */
    function grilleCinetique(scene, items) {
        var gsap = window.gsap;

        var revelation = gsap.fromTo(items,
            { opacity: 0, clipPath: 'inset(0 0 100% 0)', y: 30 },
            {
                opacity: 1,
                clipPath: 'inset(0 0 0% 0)',
                y: 0,
                duration: 1,
                ease: 'power3.out',
                stagger: { amount: 0.7, grid: 'auto', from: 'start' },
                scrollTrigger: { trigger: scene, start: 'top 82%', once: true },
            });

        var deplacements = new Map();

        Array.prototype.forEach.call(items, function (item) {
            deplacements.set(item, {
                x: gsap.quickTo(item, 'x', { duration: 0.6, ease: 'power3' }),
                y: gsap.quickTo(item, 'y', { duration: 0.6, ease: 'power3' }),
            });
        });

        function surMouvement(event) {
            var rect = scene.getBoundingClientRect();
            var ratioX = (event.clientX - rect.left) / rect.width - 0.5;
            var ratioY = (event.clientY - rect.top) / rect.height - 0.5;

            Array.prototype.forEach.call(items, function (item, index) {
                // Amplitude alternée : les vignettes ne bougent pas toutes à
                // l'identique, ce qui donne l'impression de plans superposés.
                var force = (index % 3 + 1) * 6;
                var cible = deplacements.get(item);

                cible.x(-ratioX * force);
                cible.y(-ratioY * force);
            });
        }

        function surSortie() {
            Array.prototype.forEach.call(items, function (item) {
                var cible = deplacements.get(item);

                cible.x(0);
                cible.y(0);
            });
        }

        scene.addEventListener('mousemove', surMouvement);
        scene.addEventListener('mouseleave', surSortie);

        return function () {
            scene.removeEventListener('mousemove', surMouvement);
            scene.removeEventListener('mouseleave', surSortie);

            if (revelation.scrollTrigger) {
                revelation.scrollTrigger.kill();
            }

            revelation.kill();
        };
    }

    function init() {
        var scene = document.querySelector('[data-moodboard]');

        if (!scene) {
            return;
        }

        var items = scene.querySelectorAll('[data-moodboard-item]');

        // Le CSS masque les vignettes une fois `js-pret` posée. Si GSAP n'a pas
        // pu être chargé, personne ne les révélera : on annule le masquage nous-
        // mêmes plutôt que de servir une sélection vide.
        if (!window.gsap || !window.ScrollTrigger) {
            Array.prototype.forEach.call(items, function (item) {
                item.style.opacity = '1';
            });

            return;
        }

        var gsap = window.gsap;
        var appliquer = THEMES[scene.dataset.theme] || THEMES['mosaique-flip'];

        // `registerPlugin` est idempotent : l'appeler ici évite de dépendre de
        // l'ordre d'exécution avec `chancel.js`, qui l'a probablement déjà fait.
        gsap.registerPlugin(window.ScrollTrigger);

        if (window.Flip) {
            gsap.registerPlugin(window.Flip);
        }

        gsap.matchMedia().add(
            {
                anime: '(prefers-reduced-motion: no-preference)',
                reduit: '(prefers-reduced-motion: reduce)',
            },
            function (context) {
                // Branche « mouvement réduit » : le moodboard doit rester
                // consultable et complet. On remet donc les éléments à leur état
                // final au lieu de les laisser à l'opacité 0 posée par le CSS.
                if (!context.conditions.anime) {
                    gsap.set(items, { opacity: 1, clearProps: 'transform' });

                    return;
                }

                return appliquer(scene, items);
            },
        );
    }

    window.ChancelMoodboard = { init: init };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
