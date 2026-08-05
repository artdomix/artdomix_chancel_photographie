/**
 * Import de photos par glisser-déposer.
 *
 * Les fichiers sont envoyés **un par un, en série**. C'est délibéré : sur un
 * hébergement mutualisé, chaque photo mobilise GD pour produire douze dérivés,
 * et lancer vingt requêtes en parallèle ferait tomber le serveur sur une limite
 * de mémoire ou de processus. En série, chaque requête reste courte et l'import
 * d'un lot progresse de façon régulière et prévisible.
 */
export function initUpload() {
    const zone = document.querySelector('[data-upload]');

    if (!zone) {
        return;
    }

    const champ = zone.querySelector('[data-upload-input]');
    const progression = document.querySelector('[data-upload-progression]');
    const barre = document.querySelector('[data-upload-barre]');
    const etat = document.querySelector('[data-upload-etat]');
    const resultats = document.querySelector('[data-upload-resultats]');

    const url = zone.dataset.url;
    const csrf = zone.dataset.csrf;

    let enCours = false;

    zone.addEventListener('click', () => champ.click());
    champ.addEventListener('change', () => envoyerTout([...champ.files]));

    ['dragenter', 'dragover'].forEach((type) => {
        zone.addEventListener(type, (event) => {
            event.preventDefault();
            zone.classList.add('border-corail');
        });
    });

    ['dragleave', 'drop'].forEach((type) => {
        zone.addEventListener(type, (event) => {
            event.preventDefault();
            zone.classList.remove('border-corail');
        });
    });

    zone.addEventListener('drop', (event) => {
        const fichiers = [...(event.dataTransfer?.files ?? [])].filter((f) => f.type.startsWith('image/'));
        envoyerTout(fichiers);
    });

    async function envoyerTout(fichiers) {
        if (enCours || fichiers.length === 0) {
            return;
        }

        enCours = true;
        progression.classList.remove('hidden');

        for (const [index, fichier] of fichiers.entries()) {
            etat.textContent = `Envoi de ${fichier.name} (${index + 1} sur ${fichiers.length})…`;

            await envoyer(fichier);

            barre.style.width = `${Math.round(((index + 1) / fichiers.length) * 100)}%`;
        }

        etat.textContent = `${fichiers.length} fichier(s) traité(s).`;
        enCours = false;
        champ.value = '';
    }

    async function envoyer(fichier) {
        const donnees = new FormData();
        donnees.append('fichier', fichier);

        try {
            const reponse = await fetch(url, {
                method: 'POST',
                body: donnees,
                headers: {
                    'X-CSRF-Token': csrf,
                    // Le serveur renvoie un fragment plutôt qu'une page entière.
                    'HX-Request': 'true',
                },
            });

            resultats.insertAdjacentHTML('beforeend', await reponse.text());
        } catch (erreur) {
            // Un échec réseau ne doit pas interrompre le lot : la photo suivante
            // a toutes les chances de passer.
            resultats.insertAdjacentHTML(
                'beforeend',
                `<li class="py-2 text-sm text-red-400">✕ ${fichier.name} : ${erreur.message}</li>`,
            );
        }
    }
}
