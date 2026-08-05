<?php
/**
 * Vignette d'une exposition, partagée entre la liste des expositions en cours et
 * celle des expositions passées.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Exposition $exposition
 */

$url = $this->Url->build('/expositions/' . $exposition->slug);

// Une exposition sans date de fin est une date unique, pas une période ouverte :
// afficher « du … au … » avec une moitié vide serait trompeur.
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
<article data-anim-item>
    <a href="<?= h($url) ?>">
        <?php if ($exposition->photo !== null) : ?>
            <?= $this->Photo->image($exposition->photo, 'grid', [
                'class' => 'w-full',
                'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
            ]) ?>
        <?php endif; ?>
        <h3 class="mt-4 text-lg hover:text-corail"><?= h($exposition->titre) ?></h3>
    </a>

    <?php if ($exposition->lieu) : ?>
        <p class="mt-1 text-sm text-gris"><?= h($exposition->lieu) ?></p>
    <?php endif; ?>

    <?php if ($periode !== '') : ?>
        <p class="mt-1 text-sm text-gris"><?= h($periode) ?></p>
    <?php endif; ?>
</article>
