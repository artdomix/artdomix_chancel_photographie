import '../css/app.css';

import htmx from 'htmx.org';

window.htmx = htmx;

document.documentElement.classList.add('js-pret');

/**
 * Entrée du back-office. L'uploader drag & drop et le réordonnancement des photos
 * sont ajoutés à l'étape « Admin » ; ce bundle reste volontairement séparé du
 * front pour que la page publique n'embarque pas le code d'administration.
 */
