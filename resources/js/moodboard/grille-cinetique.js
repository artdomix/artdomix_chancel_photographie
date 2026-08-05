import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/**
 * Grille cinétique : les vignettes se révèlent par un masque qui s'ouvre, et
 * réagissent légèrement à la position de la souris.
 *
 * La réaction au pointeur est passée par `quickTo`, qui réutilise la même
 * animation au lieu d'en créer une à chaque `mousemove` — sans quoi le
 * ramasse-miettes travaillerait en continu pendant tout le survol.
 */
export function appliquer(scene, items) {
    const revelation = gsap.fromTo(
        items,
        { opacity: 0, clipPath: 'inset(0 0 100% 0)', y: 30 },
        {
            opacity: 1,
            clipPath: 'inset(0 0 0% 0)',
            y: 0,
            duration: 1,
            ease: 'power3.out',
            stagger: { amount: 0.7, grid: 'auto', from: 'start' },
            scrollTrigger: { trigger: scene, start: 'top 82%', once: true },
        },
    );

    const deplacements = new Map();

    items.forEach((item) => {
        deplacements.set(item, {
            x: gsap.quickTo(item, 'x', { duration: 0.6, ease: 'power3' }),
            y: gsap.quickTo(item, 'y', { duration: 0.6, ease: 'power3' }),
        });
    });

    const surMouvement = (event) => {
        const rect = scene.getBoundingClientRect();
        const ratioX = (event.clientX - rect.left) / rect.width - 0.5;
        const ratioY = (event.clientY - rect.top) / rect.height - 0.5;

        items.forEach((item, index) => {
            // Amplitude alternée : les vignettes ne bougent pas toutes à
            // l'identique, ce qui donne l'impression de plans superposés.
            const force = (index % 3 + 1) * 6;
            const cible = deplacements.get(item);

            cible.x(-ratioX * force);
            cible.y(-ratioY * force);
        });
    };

    const surSortie = () => {
        items.forEach((item) => {
            const cible = deplacements.get(item);
            cible.x(0);
            cible.y(0);
        });
    };

    scene.addEventListener('mousemove', surMouvement);
    scene.addEventListener('mouseleave', surSortie);

    return () => {
        scene.removeEventListener('mousemove', surMouvement);
        scene.removeEventListener('mouseleave', surSortie);
        revelation.scrollTrigger?.kill();
        revelation.kill();
    };
}
