<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tag $tag
 * @var iterable $photos
 */
?>
<?= $this->Seo->partage(['titre' => $tag->nom . ' — Chancel Photographie']) ?>

<section class="mx-auto max-w-7xl px-6 py-12">
    <p class="text-sm uppercase tracking-titre text-gris"><?= h($tag->type->label()) ?></p>
    <h1 data-anim="titre" class="mt-2 text-3xl md:text-5xl"><?= h($tag->nom) ?></h1>

    <div class="mt-10">
        <?= $this->element('grille_photos', ['photos' => $photos, 'paginer' => true]) ?>
    </div>
</section>
