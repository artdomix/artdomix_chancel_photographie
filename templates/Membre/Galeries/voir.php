<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Galerie $galerie
 * @var list<int> $favoris
 */
?>
<nav class="mb-8 text-sm text-gris" aria-label="Fil d'Ariane">
    <a class="lien-souligne"
       href="<?= $this->Url->build(['prefix' => 'Membre', 'controller' => 'Tableau', 'action' => 'index']) ?>">
        Mon espace
    </a>
    <span class="mx-2">/</span>
    <span aria-current="page" class="text-blanc-casse"><?= h($galerie->nom) ?></span>
</nav>

<h1 class="text-2xl md:text-4xl"><?= h($galerie->nom) ?></h1>
<?php if ($galerie->description) : ?>
    <p class="mt-3 max-w-2xl text-gris"><?= h($galerie->description) ?></p>
<?php endif; ?>

<div class="mt-6 flex flex-wrap items-center gap-6 text-sm text-gris">
    <span><?= h((string)count($galerie->photos)) ?> photos</span>
    <?php if ($galerie->quota_favoris > 0) : ?>
        <span role="status">
            <?= h((string)count($favoris)) ?> / <?= h((string)$galerie->quota_favoris) ?> retenues
        </span>
    <?php endif; ?>
    <?php if ($galerie->telechargement_actif) : ?>
        <a class="lien-souligne text-corail"
           href="<?= $this->Url->build('/g/telecharger/' . $galerie->share_token) ?>">
            Télécharger la sélection (ZIP)
        </a>
    <?php endif; ?>
</div>

<div data-anim="grille" data-galerie
     class="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
    <?php foreach ($galerie->photos as $photo) : ?>
        <div data-anim-item>
            <?= $this->Photo->vignette($photo, [
                'sizes' => '(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw',
            ]) ?>
            <?= $this->element('bouton_favori', [
                'galerie' => $galerie,
                'photo' => $photo,
                'actif' => in_array($photo->id, $favoris, true),
            ]) ?>
        </div>
    <?php endforeach; ?>
</div>
