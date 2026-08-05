/**
 * Carte des photos géolocalisées.
 *
 * Leaflet et ses tuiles OpenStreetMap sont chargés à la demande : la carte
 * n'existe que sur une page, il serait dommage d'alourdir tout le site pour
 * elle. Le module n'est donc importé que si le conteneur est présent.
 */
export async function initCarte() {
    const conteneur = document.getElementById('carte');

    if (!conteneur) {
        return;
    }

    const [{ default: L }] = await Promise.all([
        import('leaflet'),
        import('leaflet/dist/leaflet.css'),
    ]);

    let points = [];

    try {
        points = JSON.parse(conteneur.dataset.carte ?? '[]');
    } catch {
        return;
    }

    if (points.length === 0) {
        return;
    }

    const carte = L.map(conteneur, { scrollWheelZoom: false });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(carte);

    const marqueurs = points.map((point) =>
        L.marker([point.lat, point.lng])
            .addTo(carte)
            .bindPopup(
                `<a href="${point.url}"><img src="${point.vignette}" alt="" width="160"><br>${point.titre}</a>`,
            ),
    );

    // Cadrage sur l'ensemble des points, avec une marge pour que les marqueurs
    // des bords ne soient pas collés au cadre.
    carte.fitBounds(L.featureGroup(marqueurs).getBounds(), { padding: [40, 40] });
}
