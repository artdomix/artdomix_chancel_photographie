import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/**
 * Thèmes d'animation des moodboards.
 *
 * Chaque thème est chargé à la demande : un moodboard n'en utilise qu'un, il
 * serait absurde d'embarquer les quatre. Le nom du thème vient de l'attribut
 * `data-theme` posé par le serveur d'après la colonne `moodboards.theme`.
 */
const THEMES = {
    'mosaique-flip': () => import('./mosaique-flip.js'),
    'mur-parallaxe': () => import('./mur-parallaxe.js'),
    'carrousel-pinne': () => import('./carrousel-pinne.js'),
    'grille-cinetique': () => import('./grille-cinetique.js'),
};

export async function initMoodboard() {
    const scene = document.querySelector('[data-moodboard]');

    if (!scene) {
        return;
    }

    const nom = scene.dataset.theme ?? 'mosaique-flip';
    const charger = THEMES[nom] ?? THEMES['mosaique-flip'];

    const mm = gsap.matchMedia();

    mm.add(
        {
            anime: '(prefers-reduced-motion: no-preference)',
            reduit: '(prefers-reduced-motion: reduce)',
        },
        (context) => {
            const items = scene.querySelectorAll('[data-moodboard-item]');

            // Branche « mouvement réduit » : le moodboard doit rester consultable
            // et complet. On remet donc les éléments à leur état final au lieu de
            // les laisser à l'opacité 0 posée par le CSS.
            if (!context.conditions.anime) {
                gsap.set(items, { opacity: 1, clearProps: 'transform' });

                return;
            }

            let nettoyage;

            charger().then(({ appliquer }) => {
                nettoyage = appliquer(scene, items);
            });

            return () => nettoyage?.();
        },
    );
}
