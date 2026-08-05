import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/**
 * Mur en parallaxe : les colonnes défilent à des vitesses différentes.
 *
 * L'effet est piloté par le scroll plutôt que par une durée, pour que le
 * visiteur garde le contrôle du rythme — une animation autonome sur un mur de
 * photos donne vite le tournis.
 */
export function appliquer(scene, items) {
    const colonnes = scene.querySelectorAll('[data-moodboard-colonne]');
    const declencheurs = [];

    gsap.set(items, { opacity: 1 });

    colonnes.forEach((colonne, index) => {
        // Les colonnes paires montent, les impaires descendent : le décalage
        // crée la profondeur sans que rien ne sorte du cadre.
        const amplitude = index % 2 === 0 ? -80 : 60;

        declencheurs.push(
            gsap.to(colonne, {
                y: amplitude,
                ease: 'none',
                scrollTrigger: {
                    trigger: scene,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.6,
                },
            }).scrollTrigger,
        );
    });

    const apparition = gsap.fromTo(
        items,
        { opacity: 0, y: 40 },
        {
            opacity: 1,
            y: 0,
            duration: 0.9,
            ease: 'power3.out',
            stagger: { amount: 0.8, from: 'start' },
            scrollTrigger: { trigger: scene, start: 'top 85%', once: true },
        },
    );

    return () => {
        declencheurs.forEach((d) => d?.kill());
        apparition.scrollTrigger?.kill();
        apparition.kill();
    };
}
