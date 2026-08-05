import { gsap } from 'gsap';
import { Flip } from 'gsap/Flip';

gsap.registerPlugin(Flip);

const SELECTEUR_ITEM = '[data-lightbox]';

let etat = null;

/**
 * Lightbox de galerie. L'effet demandé — la vignette qui « vole » vers le plein
 * écran — repose sur GSAP Flip : on déplace réellement le noeud dans le DOM, puis
 * Flip anime l'écart entre la position d'avant et celle d'après.
 */
export function initLightbox(racine = document) {
    racine.querySelectorAll(`${SELECTEUR_ITEM}:not([data-lightbox-pret])`).forEach((item) => {
        item.setAttribute('data-lightbox-pret', '');
        item.addEventListener('click', (event) => {
            event.preventDefault();
            ouvrir(item);
        });
    });

    if (!document.body.dataset.lightboxClavier) {
        document.body.dataset.lightboxClavier = '1';
        document.addEventListener('keydown', auClavier);
    }
}

function elementsGalerie(item) {
    const galerie = item.closest('[data-galerie]') ?? document;

    return Array.from(galerie.querySelectorAll(SELECTEUR_ITEM));
}

function ouvrir(item) {
    if (etat) {
        return;
    }

    const image = item.querySelector('img');

    if (!image) {
        return;
    }

    const conteneur = construireConteneur(item);
    const origine = image.parentElement;
    const marqueur = document.createComment('lightbox-origine');

    origine.insertBefore(marqueur, image);

    const avant = Flip.getState(image);

    conteneur.querySelector('[data-lightbox-scene]').appendChild(image);
    document.body.appendChild(conteneur);
    document.body.style.overflow = 'hidden';

    etat = { conteneur, image, marqueur, item, elements: elementsGalerie(item) };

    // L'image plein écran doit rester nette : on force la variante la plus grande.
    if (image.dataset.lightboxSrc && image.src !== image.dataset.lightboxSrc) {
        image.src = image.dataset.lightboxSrc;
        image.removeAttribute('srcset');
        image.removeAttribute('sizes');
    }

    const reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    Flip.from(avant, {
        duration: reduit ? 0 : 0.55,
        ease: 'power3.inOut',
        absolute: true,
        scale: false,
    });

    gsap.fromTo(
        conteneur.querySelector('[data-lightbox-fond]'),
        { opacity: 0 },
        { opacity: 1, duration: reduit ? 0 : 0.35 },
    );

    conteneur.querySelector('[data-lightbox-fermer]').focus();
}

function fermer() {
    if (!etat) {
        return;
    }

    const { conteneur, image, marqueur, item } = etat;
    const reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const avant = Flip.getState(image);

    marqueur.parentElement.insertBefore(image, marqueur);
    marqueur.remove();

    Flip.from(avant, {
        duration: reduit ? 0 : 0.45,
        ease: 'power3.inOut',
        absolute: true,
        scale: false,
        onComplete: () => conteneur.remove(),
    });

    document.body.style.overflow = '';
    etat = null;

    // Le focus doit revenir sur la vignette d'où l'on vient, pas en haut de page.
    item.focus({ preventScroll: true });
}

function naviguer(pas) {
    if (!etat) {
        return;
    }

    const { elements, item } = etat;
    const index = elements.indexOf(item);

    if (index === -1) {
        return;
    }

    const suivant = elements[(index + pas + elements.length) % elements.length];

    fermer();
    ouvrir(suivant);
}

function auClavier(event) {
    if (!etat) {
        return;
    }

    switch (event.key) {
        case 'Escape':
            event.preventDefault();
            fermer();
            break;
        case 'ArrowRight':
            event.preventDefault();
            naviguer(1);
            break;
        case 'ArrowLeft':
            event.preventDefault();
            naviguer(-1);
            break;
        case 'Tab':
            // Le focus reste enfermé dans la lightbox tant qu'elle est ouverte.
            piegerFocus(event);
            break;
        default:
            break;
    }
}

function piegerFocus(event) {
    const focusables = etat.conteneur.querySelectorAll('button');

    if (focusables.length === 0) {
        return;
    }

    const premier = focusables[0];
    const dernier = focusables[focusables.length - 1];

    if (event.shiftKey && document.activeElement === premier) {
        event.preventDefault();
        dernier.focus();
    } else if (!event.shiftKey && document.activeElement === dernier) {
        event.preventDefault();
        premier.focus();
    }
}

function construireConteneur(item) {
    const legende = item.dataset.lightboxLegende ?? '';
    const conteneur = document.createElement('div');

    conteneur.className = 'fixed inset-0 z-50 flex items-center justify-center';
    conteneur.setAttribute('role', 'dialog');
    conteneur.setAttribute('aria-modal', 'true');
    conteneur.setAttribute('aria-label', legende || 'Photo en plein écran');
    conteneur.innerHTML = `
        <div data-lightbox-fond class="absolute inset-0 bg-noir/95"></div>
        <div data-lightbox-scene
             class="relative flex max-h-[88vh] max-w-[92vw] items-center justify-center"></div>
        <p class="absolute bottom-6 left-1/2 -translate-x-1/2 px-4 text-center text-sm text-gris">
            ${escaper(legende)}
        </p>
        <button type="button" data-lightbox-precedent
                class="absolute left-4 top-1/2 -translate-y-1/2 p-3 text-blanc-casse hover:text-corail"
                aria-label="Photo précédente">&#8249;</button>
        <button type="button" data-lightbox-suivant
                class="absolute right-4 top-1/2 -translate-y-1/2 p-3 text-blanc-casse hover:text-corail"
                aria-label="Photo suivante">&#8250;</button>
        <button type="button" data-lightbox-fermer
                class="absolute right-4 top-4 p-3 text-blanc-casse hover:text-corail"
                aria-label="Fermer">&#10005;</button>
    `;

    conteneur.querySelector('[data-lightbox-fond]').addEventListener('click', fermer);
    conteneur.querySelector('[data-lightbox-fermer]').addEventListener('click', fermer);
    conteneur.querySelector('[data-lightbox-precedent]').addEventListener('click', () => naviguer(-1));
    conteneur.querySelector('[data-lightbox-suivant]').addEventListener('click', () => naviguer(1));

    return conteneur;
}

function escaper(texte) {
    const div = document.createElement('div');

    div.textContent = texte;

    return div.innerHTML;
}
