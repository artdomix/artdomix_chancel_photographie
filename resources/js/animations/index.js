import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, SplitText);

/**
 * Toutes les animations passent par ce matchMedia. La branche `reduce` ne se
 * contente pas de ne rien animer : elle remet explicitement les éléments à leur
 * état final, sinon ils resteraient à l'opacité 0 posée par le CSS.
 */
export function initAnimations(racine = document) {
    const cibles = racine.querySelectorAll('[data-anim]:not([data-anim-pret])');

    if (cibles.length === 0) {
        return;
    }

    // Marqué tout de suite : un swap htmx peut relancer l'init sur un noeud déjà traité.
    cibles.forEach((el) => el.setAttribute('data-anim-pret', ''));

    const mm = gsap.matchMedia();

    mm.add(
        {
            anime: '(prefers-reduced-motion: no-preference)',
            reduit: '(prefers-reduced-motion: reduce)',
        },
        (context) => {
            const { anime } = context.conditions;

            if (!anime) {
                gsap.set(cibles, { opacity: 1, y: 0, clearProps: 'transform' });

                return;
            }

            cibles.forEach((element) => {
                switch (element.dataset.anim) {
                    case 'grille':
                        animerGrille(element);
                        break;
                    case 'titre':
                        animerTitre(element);
                        break;
                    default:
                        animerApparition(element);
                }
            });
        },
    );
}

/** Révélation en cascade des vignettes d'une galerie. */
function animerGrille(grille) {
    const items = grille.querySelectorAll('[data-anim-item]');

    if (items.length === 0) {
        return;
    }

    gsap.fromTo(
        items,
        { opacity: 0, y: 40 },
        {
            opacity: 1,
            y: 0,
            duration: 0.7,
            ease: 'power3.out',
            stagger: { amount: Math.min(0.6, items.length * 0.05) },
            scrollTrigger: {
                trigger: grille,
                start: 'top 85%',
                once: true,
            },
        },
    );
}

/** Titre de section révélé mot à mot. */
function animerTitre(titre) {
    const split = new SplitText(titre, { type: 'words', wordsClass: 'inline-block' });

    gsap.fromTo(
        split.words,
        { opacity: 0, y: '0.6em' },
        {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power3.out',
            stagger: 0.04,
            scrollTrigger: {
                trigger: titre,
                start: 'top 88%',
                once: true,
            },
            // Les mots découpés cassent la sélection et les lecteurs d'écran :
            // on recolle le titre dès l'animation terminée.
            onComplete: () => split.revert(),
        },
    );
}

/** Apparition simple, cas par défaut. */
function animerApparition(element) {
    gsap.fromTo(
        element,
        { opacity: 0, y: 24 },
        {
            opacity: 1,
            y: 0,
            duration: 0.6,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: element,
                start: 'top 90%',
                once: true,
            },
        },
    );
}
