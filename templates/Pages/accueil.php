<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Photo|null $heros
 * @var iterable $selection
 * @var iterable $albums
 * @var iterable $articles
 */
?>
<?= $this->Seo->partage([
    'titre' => 'Chancel Photographie',
    'description' => 'Photographe en Seine-et-Marne. Portrait, voyage, automobile.',
    'image' => $heros,
]) ?>

<?php if ($heros !== null) : ?>
    <section class="relative h-[85vh] overflow-hidden">
        <?php // Image d'ouverture : chargée en priorité, c'est la mesure du LCP. ?>
        <?= $this->Photo->image($heros, 'large', [
            'class' => 'h-full w-full object-cover',
            'sizes' => '100vw',
            'lazy' => false,
        ]) ?>
        <div class="absolute inset-0 bg-gradient-to-t from-noir via-noir/40 to-transparent"></div>

        <div class="absolute inset-x-0 bottom-0 mx-auto max-w-7xl px-6 pb-16">
            <h1 data-anim="titre" class="text-4xl md:text-7xl">Chancel</h1>
            <p data-anim class="mt-4 max-w-lg text-lg text-gris">
                Portrait, voyage, automobile — Seine-et-Marne.
            </p>
            <a href="<?= $this->Url->build('/portfolio') ?>"
               class="mt-8 inline-block border border-corail px-8 py-3 font-display text-sm uppercase tracking-titre text-corail transition-colors hover:bg-corail hover:text-noir">
                Voir le portfolio
            </a>
        </div>
    </section>
<?php endif; ?>

<section class="mx-auto max-w-7xl px-6 py-20">
    <h2 data-anim="titre" class="mb-10 text-2xl md:text-4xl">Sélection</h2>
    <?= $this->element('grille_photos', ['photos' => $selection]) ?>
</section>

<?php if (count($albums) > 0) : ?>
    <section class="mx-auto max-w-7xl px-6 pb-20">
        <h2 data-anim="titre" class="mb-10 text-2xl md:text-4xl">Séries</h2>
        <div data-anim="grille" class="grid gap-6 md:grid-cols-3">
            <?php foreach ($albums as $album) : ?>
                <a data-anim-item class="group block" href="<?= $this->Url->build('/portfolio/' . $album->slug) ?>">
                    <?php if ($album->cover_photo !== null) : ?>
                        <div class="overflow-hidden">
                            <?= $this->Photo->image($album->cover_photo, 'grid', [
                                'class' => 'w-full transition-transform duration-700 ease-out group-hover:scale-105',
                                'sizes' => '(min-width: 768px) 33vw, 100vw',
                            ]) ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="mt-4 text-lg group-hover:text-corail"><?= h($album->nom) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (count($articles) > 0) : ?>
    <section class="mx-auto max-w-7xl border-t border-white/10 px-6 py-20">
        <h2 data-anim="titre" class="mb-10 text-2xl md:text-4xl">Journal</h2>
        <div data-anim="grille" class="grid gap-8 md:grid-cols-2">
            <?php foreach ($articles as $article) : ?>
                <article data-anim-item>
                    <time class="text-sm text-gris"><?= h($article->publie_le?->format('d/m/Y')) ?></time>
                    <h3 class="mt-2 text-xl">
                        <a class="hover:text-corail" href="<?= $this->Url->build('/blog/' . $article->slug) ?>">
                            <?= h($article->titre) ?>
                        </a>
                    </h3>
                    <?php if ($article->chapeau) : ?>
                        <p class="mt-2 text-gris"><?= h($article->chapeau) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
