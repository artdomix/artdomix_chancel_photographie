<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Exposition $exposition
 */

$periode = match (true) {
    $exposition->date_debut === null => '',
    $exposition->date_fin === null => $exposition->date_debut->format('d/m/Y'),
    default => sprintf(
        'du %s au %s',
        $exposition->date_debut->format('d/m/Y'),
        $exposition->date_fin->format('d/m/Y'),
    ),
};
?>
<?= $this->Seo->partage([
    'titre' => $exposition->titre . ' — Chancel Photographie',
    'description' => (string)($exposition->description ?? ''),
    'image' => $exposition->photo,
    'type' => 'article',
]) ?>

<section class="mx-auto max-w-4xl px-6 py-16">
    <p class="text-sm text-gris">
        <a class="lien-souligne" href="<?= $this->Url->build('/expositions') ?>">← Expositions</a>
    </p>

    <h1 data-anim="titre" class="mt-4 text-3xl md:text-5xl"><?= h($exposition->titre) ?></h1>

    <p class="mt-3 text-gris">
        <?= h($exposition->lieu ?: '') ?><?= $exposition->lieu && $periode !== '' ? ' — ' : '' ?><?= h($periode) ?>
    </p>

    <?php if ($exposition->photo !== null) : ?>
        <div data-anim class="mt-10">
            <?= $this->Photo->image($exposition->photo, 'content', [
                'class' => 'w-full',
                'sizes' => '(min-width: 896px) 896px, 100vw',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php if ($exposition->description) : ?>
        <div class="mt-10 leading-relaxed"><?= nl2br(h($exposition->description)) ?></div>
    <?php endif; ?>
</section>
