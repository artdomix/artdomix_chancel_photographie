<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $albums
 */
?>
<?= $this->Seo->partage(['titre' => 'Portfolio — Chancel Photographie']) ?>

<section class="mx-auto max-w-7xl px-6 py-16">
    <h1 data-anim="titre" class="mb-12 text-3xl md:text-5xl">Portfolio</h1>

    <div data-anim="grille" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($albums as $album) : ?>
            <a data-anim-item class="group block"
               href="<?= $this->Url->build('/portfolio/' . $album->slug) ?>">
                <div class="overflow-hidden bg-noir-clair">
                    <?php if ($album->cover_photo !== null) : ?>
                        <?= $this->Photo->image($album->cover_photo, 'grid', [
                            'class' => 'w-full transition-transform duration-700 ease-out group-hover:scale-105',
                            'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
                        ]) ?>
                    <?php else : ?>
                        <div class="aspect-[3/2] w-full bg-noir-carte"></div>
                    <?php endif; ?>
                </div>
                <h2 class="mt-4 text-lg group-hover:text-corail"><?= h($album->nom) ?></h2>
                <?php if ($album->description) : ?>
                    <p class="mt-1 text-sm text-gris"><?= h($album->description) ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
