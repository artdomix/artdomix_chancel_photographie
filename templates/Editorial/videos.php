<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $videos
 */
?>
<?= $this->Seo->partage([
    'titre' => 'Vidéos — Chancel Photographie',
    'description' => 'Films et making-of du photographe Chancel.',
]) ?>

<section class="mx-auto max-w-6xl px-6 py-16">
    <h1 data-anim="titre" class="mb-12 text-3xl md:text-5xl">Vidéos</h1>

    <?php if (count($videos) === 0) : ?>
        <p class="text-gris">Aucune vidéo publiée pour l'instant.</p>
    <?php else : ?>
        <?php
        // Les vignettes renvoient vers une fiche plutôt que d'embarquer une
        // douzaine d'iframes : chacune chargerait le lecteur de la plateforme et
        // ses traceurs, sur une page que le visiteur ne fait que parcourir.
        ?>
        <div data-anim="grille" class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($videos as $video) : ?>
                <article data-anim-item>
                    <a href="<?= $this->Url->build('/videos/' . $video->slug) ?>">
                        <?php if ($video->photo !== null) : ?>
                            <?= $this->Photo->image($video->photo, 'grid', [
                                'class' => 'w-full',
                                'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
                            ]) ?>
                        <?php endif; ?>
                        <h2 class="mt-4 text-lg hover:text-corail"><?= h($video->titre) ?></h2>
                    </a>
                    <p class="mt-1 text-sm text-gris">
                        <?= h($video->typevideo->nom ?? $video->plateforme->label()) ?>
                        <?php if ($video->duree !== null) : ?>
                            — <?= h(sprintf('%d min', (int)ceil($video->duree / 60))) ?>
                        <?php endif; ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
