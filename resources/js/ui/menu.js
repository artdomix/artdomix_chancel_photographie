/**
 * Menu principal en version mobile. Volontairement sans GSAP : c'est un élément
 * de navigation, il doit fonctionner même si la couche animation échoue.
 */
export function initMenu() {
    const bascule = document.querySelector('[data-menu-bascule]');
    const panneau = document.querySelector('[data-menu-panneau]');

    if (!bascule || !panneau) {
        return;
    }

    const fermer = () => {
        panneau.hidden = true;
        bascule.setAttribute('aria-expanded', 'false');
    };

    bascule.addEventListener('click', () => {
        const ouvert = bascule.getAttribute('aria-expanded') === 'true';

        panneau.hidden = ouvert;
        bascule.setAttribute('aria-expanded', String(!ouvert));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && bascule.getAttribute('aria-expanded') === 'true') {
            fermer();
            bascule.focus();
        }
    });

    // Le panneau reste ouvert si l'on repasse en large : on le referme au resize.
    window.matchMedia('(min-width: 768px)').addEventListener('change', fermer);
}
