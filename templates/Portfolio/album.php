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
    // Le chapô sert d'abord ici : c'est le texte court fait pour être lu hors
    // de son contexte, dans un aperçu de partage ou un résultat de recherche.
    'description' => (string)($album->chapeau ?: $album->description ?: 'Galerie photo.'),
    'image' => $album->cover_photo,
]) ?>
<?= $this->Seo->filAriane($fil) ?>

<?php if ($album->cover_photo !== null) : ?>
    <?php
    // Ouverture pleine largeur : une série se présente par une image, pas par un
    // titre. Elle n'est pas différée, c'est elle que mesure le LCP.
    ?>
    <figure class="relative">
        <?= $this->Photo->image($album->cover_photo, 'large', [
            'class' => 'h-[45vh] w-full object-cover md:h-[70vh]',
            'sizes' => '100vw',
            'lazy' => false,
        ]) ?>
        <div class="absolute inset-0 bg-gradient-to-t from-noir via-noir/30 to-transparent"></div>

        <figcaption class="absolute inset-x-0 bottom-0 mx-auto max-w-7xl px-6 pb-10">
            <h1 data-anim="titre" class="text-3xl md:text-6xl"><?= h($album->nom) ?></h1>
            <?php if ($album->chapeau) : ?>
                <p data-anim class="mt-4 max-w-2xl text-lg text-gris-clair"><?= h($album->chapeau) ?></p>
            <?php endif; ?>
        </figcaption>
    </figure>
<?php endif; ?>

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

    <?php if ($album->cover_photo === null) : ?>
        <h1 data-anim="titre" class="text-3xl md:text-5xl"><?= h($album->nom) ?></h1>
        <?php if ($album->chapeau) : ?>
            <p data-anim class="mt-4 max-w-2xl text-lg text-gris-clair"><?= h($album->chapeau) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($album->description) : ?>
        <?php
        // Texte d'intention, en pleine largeur de lecture : c'est le propos de la
        // série, pas une légende.
        ?>
        <div data-anim class="mt-6 max-w-2xl leading-relaxed text-gris">
            <?= nl2br(h($album->description)) ?>
        </div>
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
        <?= $this->element('grille_photos', [
            'photos' => $photos,
            'paginer' => true,
            'serie' => $album->slug,
        ]) ?>
    </div>
</section>
