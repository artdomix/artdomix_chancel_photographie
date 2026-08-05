<?php
/**
 * @var \App\View\AppView $this
 * @var array $courantes
 * @var array $passees
 */
?>
<?= $this->Seo->partage([
    'titre' => 'Expositions — Chancel Photographie',
    'description' => 'Expositions en cours et passées du photographe Chancel.',
]) ?>

<section class="mx-auto max-w-6xl px-6 py-16">
    <h1 data-anim="titre" class="mb-12 text-3xl md:text-5xl">Expositions</h1>

    <?php if ($courantes === [] && $passees === []) : ?>
        <p class="text-gris">Aucune exposition annoncée pour l'instant.</p>
    <?php endif; ?>

    <?php if ($courantes !== []) : ?>
        <h2 class="mb-8 text-xl">En cours et à venir</h2>
        <div data-anim="grille" class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($courantes as $exposition) : ?>
                <?= $this->element('carte_exposition', compact('exposition')) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($passees !== []) : ?>
        <h2 class="mb-8 <?= $courantes !== [] ? 'mt-16' : '' ?> text-xl text-gris">Passées</h2>
        <div data-anim="grille" class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($passees as $exposition) : ?>
                <?= $this->element('carte_exposition', compact('exposition')) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
