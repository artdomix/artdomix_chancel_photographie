<?php
/**
 * @var \App\View\AppView $this
 * @var int $nbPhotos
 * @var int $nbAlbums
 * @var int $nbMoodboards
 * @var int $nbMessagesNonLus
 * @var iterable $dernieresPhotos
 */
?>
<h1 class="mb-8 text-2xl">Tableau de bord</h1>

<div class="grid grid-cols-2 gap-4 md:grid-cols-4">
    <?php foreach (
    [
        'Photos' => $nbPhotos,
        'Albums' => $nbAlbums,
        'Moodboards' => $nbMoodboards,
        'Messages non lus' => $nbMessagesNonLus,
    ] as $libelle => $valeur
) : ?>
        <div class="border border-white/10 bg-noir-carte p-5">
            <div class="font-display text-3xl text-corail"><?= h((string)$valeur) ?></div>
            <div class="mt-1 text-sm text-gris"><?= h($libelle) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="mb-4 mt-10 text-lg">Dernières photos</h2>
<ul class="space-y-2 text-sm">
    <?php foreach ($dernieresPhotos as $photo) : ?>
        <li class="flex justify-between border-b border-white/5 py-2">
            <span><?= h($photo->titre ?? $photo->fichier) ?></span>
            <span class="text-gris">
                <?= h($photo->exif->date_capture?->format('d/m/Y') ?? '—') ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>
