<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $tirages
 */
?>
<?= $this->Seo->partage([
    'titre' => 'Tirages — Chancel Photographie',
    'description' => 'Tirages d\'art signés, formats et papiers disponibles.',
]) ?>

<section class="mx-auto max-w-6xl px-6 py-16">
    <h1 data-anim="titre" class="mb-4 text-3xl md:text-5xl">Tirages</h1>
    <p class="mb-12 max-w-2xl text-gris">
        Chaque photographie du portfolio peut être tirée sur commande. Les
        formats et papiers ci-dessous sont ceux tenus en stock ; pour un autre
        format, <a class="lien-souligne" href="<?= $this->Url->build('/contact') ?>">écrivez-moi</a>.
    </p>

    <?php if (count($tirages) === 0) : ?>
        <p class="text-gris">Aucun tirage proposé pour l'instant.</p>
    <?php else : ?>
        <div data-anim="grille" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($tirages as $tirage) : ?>
                <article data-anim-item class="border border-white/10">
                    <?php if ($tirage->photo !== null) : ?>
                        <a href="<?= $this->Url->build('/photo/' . $tirage->photo->slug) ?>">
                            <?= $this->Photo->image($tirage->photo, 'grid', [
                                'class' => 'w-full',
                                'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
                            ]) ?>
                        </a>
                    <?php endif; ?>

                    <div class="p-5">
                        <h2 class="text-lg">
                            <?= h($tirage->photo->titre ?? 'Tirage') ?>
                        </h2>
                        <dl class="mt-3 space-y-1 text-sm text-gris">
                            <?php if ($tirage->typetirage !== null) : ?>
                                <div class="flex gap-2">
                                    <dt>Type</dt>
                                    <dd class="text-blanc-casse"><?= h($tirage->typetirage->nom) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($tirage->format) : ?>
                                <div class="flex gap-2">
                                    <dt>Format</dt>
                                    <dd class="text-blanc-casse"><?= h($tirage->format) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($tirage->papier) : ?>
                                <div class="flex gap-2">
                                    <dt>Papier</dt>
                                    <dd class="text-blanc-casse"><?= h($tirage->papier) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($tirage->tirage_limite !== null) : ?>
                                <div class="flex gap-2">
                                    <dt>Édition</dt>
                                    <dd class="text-blanc-casse">
                                        limitée à <?= h((string)$tirage->tirage_limite) ?> exemplaires
                                    </dd>
                                </div>
                            <?php endif; ?>
                        </dl>

                        <?php if ($tirage->prix !== null) : ?>
                            <p class="mt-4 text-lg"><?= h(number_format((float)$tirage->prix, 2, ',', ' ')) ?> €</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
