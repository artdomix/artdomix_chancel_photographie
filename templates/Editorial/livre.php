<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Livre $livre
 */
?>
<?= $this->Seo->partage([
    'titre' => $livre->titre . ' — Chancel Photographie',
    'description' => (string)($livre->description ?? ''),
    'image' => $livre->photo,
    'type' => 'book',
]) ?>

<section class="mx-auto grid max-w-5xl gap-12 px-6 py-16 md:grid-cols-2">
    <?php if ($livre->photo !== null) : ?>
        <div data-anim>
            <?= $this->Photo->image($livre->photo, 'content', [
                'class' => 'w-full',
                'sizes' => '(min-width: 768px) 50vw, 100vw',
            ]) ?>
        </div>
    <?php endif; ?>

    <div class="<?= $livre->photo !== null ? '' : 'md:col-span-2' ?>">
        <p class="text-sm text-gris">
            <a class="lien-souligne" href="<?= $this->Url->build('/livres') ?>">← Livres</a>
        </p>
        <h1 data-anim="titre" class="mt-4 text-3xl md:text-4xl"><?= h($livre->titre) ?></h1>

        <p class="mt-3 text-sm text-gris">
            <?= $livre->date_parution !== null
                ? 'Paru en ' . h($livre->date_parution->format('Y'))
                : 'À paraître' ?>
        </p>

        <?php if ($livre->description) : ?>
            <div class="mt-6 leading-relaxed"><?= nl2br(h($livre->description)) ?></div>
        <?php endif; ?>

        <?php if ($livre->prix !== null) : ?>
            <p class="mt-8 text-xl"><?= h(number_format((float)$livre->prix, 2, ',', ' ')) ?> €</p>
        <?php endif; ?>

        <?php if ($livre->lien_achat) : ?>
            <?php // `noopener` obligatoire avec `target="_blank"` : sans lui, la page ouverte garde la main sur celle-ci. ?>
            <p class="mt-6">
                <a class="inline-block border border-corail px-6 py-3 font-display text-sm uppercase tracking-titre text-corail hover:bg-corail hover:text-noir"
                   href="<?= h($livre->lien_achat) ?>" target="_blank" rel="noopener nofollow">
                    Commander
                </a>
            </p>
        <?php endif; ?>
    </div>
</section>
