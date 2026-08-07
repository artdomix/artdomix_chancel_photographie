<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Photo $photo
 * @var \App\Model\Entity\Album|null $serie
 * @var \App\Model\Entity\Photo|null $precedente
 * @var \App\Model\Entity\Photo|null $suivante
 * @var array<\App\Model\Entity\Photo> $memeSerie
 * @var iterable $ancetres
 */

// Le lien vers une photo emporte sa série : sans ce paramètre, la voisine
// affichée retomberait sur la première série de la photo, qui n'est pas
// forcément celle que le visiteur est en train de parcourir.
$versPhoto = fn(\App\Model\Entity\Photo $cible): string => $this->Url->build(
    '/photo/' . $cible->slug . ($serie !== null ? '?serie=' . $serie->slug : ''),
);

$fil = [['nom' => 'Portfolio', 'url' => '/portfolio']];
$chemin = '';

foreach ($ancetres as $ancetre) {
    $chemin .= '/' . $ancetre->slug;
    $fil[] = ['nom' => $ancetre->nom, 'url' => '/portfolio' . $chemin];
}
?>
<?= $this->Seo->partage([
    'titre' => ($photo->titre ?? 'Photo') . ' — Chancel Photographie',
    'description' => (string)($photo->description ?: $photo->alt ?: ''),
    'image' => $photo,
    'type' => 'article',
]) ?>
<?= $this->Seo->photoJsonLd($photo) ?>
<?php if ($serie !== null) : ?>
    <?= $this->Seo->filAriane($fil) ?>
<?php endif; ?>

<article class="mx-auto max-w-5xl px-6 py-12">
    <?php if ($serie !== null) : ?>
        <nav aria-label="Fil d'Ariane" class="mb-8 text-sm text-gris">
            <?php foreach ($fil as $i => $etape) : ?>
                <?php if ($i > 0) :
                    ?><span class="mx-2">/</span><?php
                endif; ?>
                <a class="lien-souligne" href="<?= $this->Url->build($etape['url']) ?>"><?= h($etape['nom']) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

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

    <?php if ($precedente !== null || $suivante !== null) : ?>
        <?php
        // Précédent / suivant dans l'ordre de la série, celui que le photographe
        // a choisi. C'est ce qui fait d'une photo une étape plutôt qu'un
        // aboutissement.
        ?>
        <nav aria-label="Navigation dans la série"
             class="mt-12 flex items-stretch justify-between gap-6 border-t border-white/10 pt-8">
            <?php if ($precedente !== null) : ?>
                <a class="group flex items-center gap-4" href="<?= h($versPhoto($precedente)) ?>" rel="prev">
                    <span aria-hidden="true" class="text-2xl text-gris group-hover:text-corail">&#8249;</span>
                    <img src="<?= h($this->Photo->url($precedente, 'thumb', 'jpeg')) ?>" alt=""
                         width="80" height="53" loading="lazy" class="h-14 w-20 object-cover">
                    <span class="hidden text-sm text-gris group-hover:text-corail sm:block">
                        <?= h($precedente->titre ?? 'Photo précédente') ?>
                    </span>
                </a>
            <?php else : ?>
                <span></span>
            <?php endif; ?>

            <?php if ($suivante !== null) : ?>
                <a class="group flex items-center gap-4 text-right" href="<?= h($versPhoto($suivante)) ?>" rel="next">
                    <span class="hidden text-sm text-gris group-hover:text-corail sm:block">
                        <?= h($suivante->titre ?? 'Photo suivante') ?>
                    </span>
                    <img src="<?= h($this->Photo->url($suivante, 'thumb', 'jpeg')) ?>" alt=""
                         width="80" height="53" loading="lazy" class="h-14 w-20 object-cover">
                    <span aria-hidden="true" class="text-2xl text-gris group-hover:text-corail">&#8250;</span>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <?php if ($memeSerie !== []) : ?>
        <section class="mt-12 border-t border-white/10 pt-8">
            <h2 class="mb-6 text-lg">
                Dans la même série —
                <a class="lien-souligne text-corail"
                   href="<?= $this->Url->build('/portfolio' . $chemin) ?>"><?= h($serie->nom) ?></a>
            </h2>

            <ul class="grid grid-cols-3 gap-3 md:grid-cols-6">
                <?php foreach ($memeSerie as $voisine) : ?>
                    <li>
                        <a href="<?= h($versPhoto($voisine)) ?>">
                            <img src="<?= h($this->Photo->url($voisine, 'thumb', 'jpeg')) ?>"
                                 alt="<?= h((string)($voisine->titre ?? '')) ?>"
                                 width="320" height="213" loading="lazy"
                                 class="w-full object-cover transition-opacity hover:opacity-75">
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</article>