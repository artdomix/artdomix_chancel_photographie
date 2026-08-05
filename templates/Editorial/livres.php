<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $livres
 */
?>
<?= $this->Seo->partage([
    'titre' => 'Livres — Chancel Photographie',
    'description' => 'Ouvrages et publications du photographe Chancel.',
]) ?>

<section class="mx-auto max-w-6xl px-6 py-16">
    <h1 data-anim="titre" class="mb-12 text-3xl md:text-5xl">Livres</h1>

    <?php if (count($livres) === 0) : ?>
        <p class="text-gris">Aucun ouvrage publié pour l'instant.</p>
    <?php else : ?>
        <div data-anim="grille" class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($livres as $livre) : ?>
                <article data-anim-item>
                    <a href="<?= $this->Url->build('/livres/' . $livre->slug) ?>">
                        <?php if ($livre->photo !== null) : ?>
                            <?= $this->Photo->image($livre->photo, 'grid', [
                                'class' => 'w-full',
                                'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
                            ]) ?>
                        <?php endif; ?>
                        <h2 class="mt-4 text-lg hover:text-corail"><?= h($livre->titre) ?></h2>
                    </a>
                    <p class="mt-1 text-sm text-gris">
                        <?php if ($livre->date_parution !== null) : ?>
                            <?= h($livre->date_parution->format('Y')) ?>
                        <?php else : ?>
                            À paraître
                        <?php endif; ?>
                        <?php if ($livre->prix !== null) : ?>
                            — <?= h(number_format((float)$livre->prix, 2, ',', ' ')) ?> €
                        <?php endif; ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
