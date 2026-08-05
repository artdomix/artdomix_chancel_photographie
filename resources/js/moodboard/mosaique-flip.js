import { gsap } from 'gsap';
import { Flip } from 'gsap/Flip';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(Flip, ScrollTrigger);

/**
 * Mosaïque animée par GSAP Flip.
 *
 * Les vignettes arrivent groupées au centre puis se déploient vers leur position
 * définitive. Flip mesure la position de départ et celle d'arrivée, et anime
 * l'écart : la mise en page reste celle du CSS, aucune coordonnée n'est calculée
 * à la main.
 */
export function appliquer(scene, items) {
    if (items.length === 0) {
        return () => {};
    }

    const etat = Flip.getState(items);

    // État de départ : tout est empilé au centre, réduit et légèrement tourné.
    gsap.set(items, {
        opacity: 0,
        scale: 0.6,
        xPercent: (i) => (i % 2 === 0 ? -18 : 18),
        yPercent: 12,
        rotation: (i) => (i % 3 - 1) * 4,
    });

    const timeline = Flip.from(etat, {
        duration: 1.1,
        ease: 'power3.out',
        stagger: { amount: 0.5, from: 'center' },
        absolute: false,
        paused: true,
        onStart: () => gsap.to(items, { opacity: 1, duration: 0.5, stagger: { amount: 0.5 } }),
    });

    const declencheur = ScrollTrigger.create({
        trigger: scene,
        start: 'top 80%',
        once: true,
        onEnter: () => timeline.play(),
    });

    // Survol : la vignette passe au premier plan sans bousculer ses voisines.
    const survols = [];

    items.forEach((item) => {
        const entrer = () => gsap.to(item, { scale: 1.04, zIndex: 10, duration: 0.4, ease: 'power2.out' });
        const sortir = () => gsap.to(item, { scale: 1, zIndex: 1, duration: 0.4, ease: 'power2.out' });

        item.addEventListener('mouseenter', entrer);
        item.addEventListener('mouseleave', sortir);
        survols.push(() => {
            item.removeEventListener('mouseenter', entrer);
            item.removeEventListener('mouseleave', sortir);
        });
    });

    return () => {
        declencheur.kill();
        timeline.kill();
        survols.forEach((retirer) => retirer());
    };
}
