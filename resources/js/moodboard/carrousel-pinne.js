import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/**
 * Carrousel horizontal épinglé : la section reste fixe pendant que les photos
 * défilent latéralement, au rythme du scroll vertical.
 *
 * Volontairement désactivé sous 768 px : sur un écran étroit, immobiliser la
 * page pour imposer un défilement horizontal est plus déroutant qu'élégant, et
 * cela casse le geste de scroll attendu sur mobile.
 */
export function appliquer(scene, items) {
    const piste = scene.querySelector('[data-moodboard-piste]');

    gsap.set(items, { opacity: 1 });

    if (!piste || window.innerWidth < 768) {
        // Repli : simple révélation en cascade, la piste défile nativement.
        const simple = gsap.fromTo(
            items,
            { opacity: 0, x: 40 },
            {
                opacity: 1,
                x: 0,
                duration: 0.7,
                ease: 'power2.out',
                stagger: 0.08,
                scrollTrigger: { trigger: scene, start: 'top 85%', once: true },
            },
        );

        return () => {
            simple.scrollTrigger?.kill();
            simple.kill();
        };
    }

    const distance = () => piste.scrollWidth - scene.offsetWidth;

    const animation = gsap.to(piste, {
        x: () => -distance(),
        ease: 'none',
        scrollTrigger: {
            trigger: scene,
            start: 'top top',
            // La hauteur de scroll épinglée correspond à la largeur à parcourir :
            // le défilement horizontal avance donc au même rythme que le vertical.
            end: () => '+=' + distance(),
            pin: true,
            scrub: 0.8,
            anticipatePin: 1,
            invalidateOnRefresh: true,
        },
    });

    return () => {
        animation.scrollTrigger?.kill();
        animation.kill();
    };
}
