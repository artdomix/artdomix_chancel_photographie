<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Photo $photo
 */
?>
<?= $this->Seo->partage([
    'titre' => ($photo->titre ?? 'Photo') . ' — Chancel Photographie',
    'description' => (string)($photo->description ?: $photo->alt ?: ''),
    'image' => $photo,
    'type' => 'article',
]) ?>
<?= $this->Seo->photoJsonLd($photo) ?>

<article class="mx-auto max-w-5xl px-6 py-12">
    <?php // Image principale : jamais différée, c'est elle que mesure le LCP. ?>
    <?= $this->Photo->image($photo, 'large', [
        'class' => 'w-full',
        'sizes' => '(min-width: 1024px) 1024px, 100vw',
        'lazy' => false,
    ]) ?>

    <header class="mt-8">
        <h1 class="text-2xl md:text-4xl"><?= h($photo->titre ?? '') ?></h1>
        <?php if ($photo->description) : ?>
            <p class="mt-4 max-w-2xl text-gris"><?= h($photo->description) ?></p>
        <?php endif; ?>
    </header>

    <?php if (count($photo->tags) > 0) : ?>
        <nav aria-label="Tags" class="mt-6 flex flex-wrap gap-2">
            <?php foreach ($photo->tags as $tag) : ?>
                <a class="border border-white/20 px-3 py-1 text-xs uppercase tracking-titre hover:border-corail hover:text-corail"
                   href="<?= $this->Url->build('/tag/' . $tag->slug) ?>">
                    <?= h($tag->nom) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($photo->exif !== null) : ?>
        <?php
        $lignes = array_filter([
            'Boîtier' => $photo->exif->apn,
            'Objectif' => $photo->exif->objectif,
            'Ouverture' => $photo->exif->ouverture,
            'Vitesse' => $photo->exif->exposition,
            'ISO' => $photo->exif->iso,
            'Focale' => $photo->exif->focale,
            'Prise le' => $photo->exif->date_capture?->format('d/m/Y'),
        ]);
        ?>
        <?php if ($lignes !== []) : ?>
            <dl class="mt-10 grid grid-cols-2 gap-x-8 gap-y-3 border-t border-white/10 pt-6 text-sm md:grid-cols-4">
                <?php foreach ($lignes as $libelle => $valeur) : ?>
                    <div>
                        <dt class="text-gris"><?= h($libelle) ?></dt>
                        <dd><?= h((string)$valeur) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    <?php endif; ?>
</article>
