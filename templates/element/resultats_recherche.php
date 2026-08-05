<?php
/**
 * Fragment des résultats de recherche, rafraîchi par htmx à chaque frappe.
 *
 * @var \App\View\AppView $this
 * @var string $terme
 * @var iterable $photos
 */
?>
<div id="resultats">
    <?php if ($terme === '') : ?>
        <p class="py-12 text-center text-gris">Saisissez un mot-clé, un lieu, un boîtier…</p>
    <?php elseif (count($photos) === 0) : ?>
        <p class="py-12 text-center text-gris">
            Aucun résultat pour « <?= h($terme) ?> ».
        </p>
    <?php else : ?>
        <p class="mb-6 text-sm text-gris" role="status">
            <?= h((string)count($photos)) ?> résultat(s) pour « <?= h($terme) ?> »
        </p>
        <?= $this->element('grille_photos', ['photos' => $photos, 'paginer' => true]) ?>
    <?php endif; ?>
</div>
