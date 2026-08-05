<?php
/**
 * @var \App\View\AppView $this
 * @var array $points
 * @var int $nbPoints
 */

// Leaflet n'est chargé que sur cette page : les 150 Ko de la bibliothèque n'ont
// rien à faire sur les autres. Le bloc « script » est rendu après `front()`,
// donc `window.L` existe avant l'exécution différée de chancel.js.
$this->append('script', $this->Assets->carte());
?>
<?= $this->Seo->partage(['titre' => 'Carte — Chancel Photographie']) ?>

<section class="mx-auto max-w-7xl px-6 py-12">
    <h1 data-anim="titre" class="mb-4 text-3xl md:text-5xl">Carte</h1>
    <p class="mb-8 text-gris">
        <?= h((string)$nbPoints) ?> photo(s) géolocalisée(s), d'après les données GPS des fichiers.
    </p>

    <?php if ($nbPoints === 0) : ?>
        <p class="py-16 text-center text-gris">Aucune photo géolocalisée pour l'instant.</p>
    <?php else : ?>
        <?php
        // Les points sont passés en JSON dans un attribut de données plutôt que
        // dans un <script> inline : cela évite d'avoir à assouplir la CSP.
        ?>
        <div id="carte" class="h-[70vh] w-full border border-white/10"
             data-carte='<?= h(json_encode($points, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'
             role="application"
             aria-label="Carte des photos géolocalisées"></div>

        <noscript>
            <ul class="mt-6 space-y-2 text-sm">
                <?php foreach ($points as $point) : ?>
                    <li>
                        <a class="lien-souligne" href="<?= h($point['url']) ?>"><?= h($point['titre']) ?></a>
                        <span class="text-gris">(<?= h((string)$point['lat']) ?>, <?= h((string)$point['lng']) ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </noscript>
    <?php endif; ?>
</section>
