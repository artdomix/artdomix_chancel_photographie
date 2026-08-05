/**
 * Réordonnancement des photos d'un album à la souris.
 *
 * Utilise l'API HTML5 native de glisser-déposer plutôt qu'une bibliothèque :
 * le besoin se limite à réordonner une grille, et une dépendance de plus serait
 * à suivre à chaque montée de version pour un gain nul.
 *
 * Un contrôle clavier double le geste souris : sans lui, réordonner un album
 * serait impossible pour qui n'utilise pas de souris.
 */
export function initTri() {
    const grille = document.querySelector('[data-tri]');

    if (!grille) {
        return;
    }

    const url = grille.dataset.triUrl;
    const csrf = grille.dataset.csrf;
    let source = null;

    const items = () => [...grille.querySelectorAll('[data-tri-item]')];

    items().forEach((item) => {
        item.draggable = true;

        item.addEventListener('dragstart', () => {
            source = item;
            item.classList.add('opacity-40');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-40');
            enregistrer();
        });

        item.addEventListener('dragover', (event) => {
            event.preventDefault();

            if (!source || source === item) {
                return;
            }

            const rect = item.getBoundingClientRect();
            const apres = event.clientX > rect.left + rect.width / 2;

            item.parentNode.insertBefore(source, apres ? item.nextSibling : item);
        });

        // Flèches gauche/droite : déplace l'élément focalisé d'un cran.
        item.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();

            const liste = items();
            const index = liste.indexOf(item);
            const cible = event.key === 'ArrowLeft' ? index - 1 : index + 1;

            if (cible < 0 || cible >= liste.length) {
                return;
            }

            if (event.key === 'ArrowLeft') {
                liste[cible].parentNode.insertBefore(item, liste[cible]);
            } else {
                liste[cible].parentNode.insertBefore(item, liste[cible].nextSibling);
            }

            item.focus();
            enregistrer();
        });
    });

    let minuteur;

    function enregistrer() {
        // Regroupe les déplacements successifs : réordonner dix photos ne doit
        // pas produire dix requêtes.
        clearTimeout(minuteur);

        minuteur = setTimeout(() => {
            const ordre = items().map((item) => item.dataset.triItem);

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf,
                },
                body: JSON.stringify({ ordre }),
            }).then((reponse) => {
                const etat = grille.querySelector('[data-tri-etat]')
                    ?? document.querySelector('[data-tri-etat]');

                if (etat) {
                    etat.textContent = reponse.ok ? 'Ordre enregistré.' : "L'ordre n'a pas pu être enregistré.";
                }
            });
        }, 600);
    }
}
