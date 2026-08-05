import '../css/app.css';

import htmx from 'htmx.org';

import { initTri } from './admin/tri.js';
import { initUpload } from './admin/upload.js';

window.htmx = htmx;

document.documentElement.classList.add('js-pret');

/**
 * Entrée du back-office, séparée du front : la page publique n'a aucune raison
 * d'embarquer l'uploader ni le réordonnancement.
 */
const demarrer = () => {
    initUpload();
    initTri();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarrer);
} else {
    demarrer();
}
