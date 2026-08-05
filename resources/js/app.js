import '../css/app.css';

import htmx from 'htmx.org';

import { initAnimations } from './animations/index.js';
import { initLightbox } from './animations/lightbox.js';
import { initMoodboard } from './moodboard/index.js';
import { initCarte } from './ui/carte.js';
import { initMenu } from './ui/menu.js';

// htmx est utilisé depuis les attributs HTML : il doit être joignable globalement.
window.htmx = htmx;

/**
 * Signale au CSS que le JS a pris la main. Tant que cette classe est absente,
 * les éléments `[data-anim]` restent visibles — une page reste donc lisible même
 * si ce bundle ne se charge pas.
 */
document.documentElement.classList.add('js-pret');

const demarrer = () => {
    initMenu();
    initCarte();
    initMoodboard();
    initAnimations();
    initLightbox();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarrer);
} else {
    demarrer();
}

/**
 * Après un échange htmx, seul le fragment remplacé doit être ré-animé — rejouer
 * l'ensemble ferait clignoter la page déjà en place.
 */
document.body.addEventListener('htmx:afterSwap', (event) => {
    initAnimations(event.detail.target);
    initLightbox(event.detail.target);
});
