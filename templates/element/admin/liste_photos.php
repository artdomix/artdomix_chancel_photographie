<?php
/**
 * Tableau des photos, rafraîchi par htmx lors d'une recherche.
 *
 * @var \App\View\AppView $this
 * @var iterable $photos
 */
?>
<div id="liste-photos">
    <table class="w-full text-sm">
        <caption class="sr-only">Photos du site</caption>
        <thead class="border-b border-white/10 text-left text-gris">
            <tr>
                <th scope="col" class="w-10 py-2"></th>
                <th scope="col" class="py-2">Aperçu</th>
                <th scope="col" class="py-2">Titre</th>
                <th scope="col" class="py-2">Prise le</th>
                <th scope="col" class="py-2">État</th>
                <th scope="col" class="py-2 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($photos as $photo) : ?>
                <tr class="border-b border-white/5">
                    <td class="py-2">
                        <input type="checkbox" name="ids[]" value="<?= h((string)$photo->id) ?>"
                               aria-label="Sélectionner <?= h((string)($photo->titre ?? $photo->fichier)) ?>">
                    </td>
                    <td class="py-2">
                        <img src="<?= h($this->Photo->url($photo, 'thumb', 'jpeg')) ?>" alt=""
                             width="64" height="43" loading="lazy" class="h-11 w-16 object-cover">
                    </td>
                    <td class="py-2"><?= h($photo->titre ?? $photo->fichier) ?></td>
                    <td class="py-2 text-gris"><?= h($photo->exif->date_capture?->format('d/m/Y') ?? '—') ?></td>
                    <td class="py-2">
                        <span class="<?= $photo->actif ? 'text-corail' : 'text-gris' ?>">
                            <?= $photo->actif ? 'En ligne' : 'Masquée' ?>
                        </span>
                    </td>
                    <td class="py-2 text-right">
                        <a class="lien-souligne"
                           href="<?= $this->Url->build(['action' => 'modifier', $photo->id]) ?>">Modifier</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <nav class="mt-6 flex justify-between text-sm" aria-label="Pagination">
        <?= $this->Paginator->prev('← Précédent', ['class' => 'lien-souligne']) ?>
        <span class="text-gris"><?= $this->Paginator->counter('Page {{page}} sur {{pages}}') ?></span>
        <?= $this->Paginator->next('Suivant →', ['class' => 'lien-souligne']) ?>
    </nav>
</div>
