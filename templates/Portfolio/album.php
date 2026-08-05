<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Album $album
 * @var iterable $sousAlbums
 * @var iterable $photos
 * @var iterable $ancetres
 */

$fil = [['nom' => 'Portfolio', 'url' => '/portfolio']];
$chemin = '';

foreach ($ancetres as $ancetre) {
    $chemin .= '/' . $ancetre->slug;
    $fil[] = ['nom' => $ancetre->nom, 'url' => '/portfolio' . $chemin];
}
?>
<?= $this->Seo->partage([
    'titre' => $album->nom . ' — Chancel Photographie',
    'description' => (string)($album->description ?: 'Galerie photo.'),
    'image' => $album->cover_photo,
]) ?>
<?= $this->Seo->filAriane($fil) ?>

<section class="mx-auto max-w-7xl px-6 py-12">
    <nav aria-label="Fil d'Ariane" class="mb-8 text-sm text-gris">
        <?php foreach ($fil as $i => $etape) : ?>
            <?php if ($i > 0) :
                ?><span class="mx-2">/</span><?php
            endif; ?>
            <?php if ($i === count($fil) - 1) : ?>
                <span aria-current="page" class="text-blanc-casse"><?= h($etape['nom']) ?></span>
            <?php else : ?>
                <a class="lien-souligne" href="<?= $this->Url->build($etape['url']) ?>"><?= h($etape['nom']) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <h1 data-anim="titre" class="text-3xl md:text-5xl"><?= h($album->nom) ?></h1>
    <?php if ($album->description) : ?>
        <p data-anim class="mt-4 max-w-2xl text-gris"><?= h($album->description) ?></p>
    <?php endif; ?>

    <?php if (count($sousAlbums) > 0) : ?>
        <nav aria-label="Sous-albums" class="mt-8 flex flex-wrap gap-3">
            <?php foreach ($sousAlbums as $sous) : ?>
                <a class="border border-white/20 px-4 py-2 text-sm uppercase tracking-titre hover:border-corail hover:text-corail"
                   href="<?= $this->Url->build('/portfolio' . $chemin . '/' . $sous->slug) ?>">
                    <?= h($sous->nom) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="mt-10">
        <?= $this->element('grille_photos', ['photos' => $photos, 'paginer' => true]) ?>
    </div>
</section>
