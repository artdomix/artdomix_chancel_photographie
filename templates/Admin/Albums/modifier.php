<?php

use App\Model\Enum\VisibiliteAlbum;

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Album $album
 * @var array $parents
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
?>
<h1 class="mb-8 text-2xl">
    <?= $album->isNew() ? 'Nouvel album' : 'Modifier « ' . h($album->nom) . ' »' ?>
</h1>

<?= $this->Form->create($album, ['class' => 'max-w-xl space-y-5']) ?>
    <?= $this->Form->control('nom', ['label' => ['class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('description', ['type' => 'textarea', 'rows' => 3, 'label' => ['class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('parent_id', ['type' => 'select', 'options' => $parents, 'empty' => '— Racine —', 'label' => ['text' => 'Album parent', 'class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('visibilite', ['type' => 'select', 'options' => VisibiliteAlbum::options(), 'label' => ['class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('actif', ['label' => ['text' => 'En ligne', 'class' => 'text-sm text-gris']]) ?>
    <?= $this->Form->button('Enregistrer', ['class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre']) ?>
<?= $this->Form->end() ?>

<?php
// Le rattachement et l'ordre des photos sont pris en charge par le module de
// sélection, commun aux albums, moodboards et galeries : les dupliquer ici
// ferait diverger deux écrans qui rendent le même service.
?>
<?php if (!$album->isNew()) : ?>
    <div class="mt-10 border-t border-white/10 pt-6">
        <p class="mb-4 text-sm text-gris">
            <?= h((string)count($album->photos)) ?> photo(s) dans cet album.
        </p>
        <a class="inline-block border border-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-corail hover:bg-corail hover:text-noir"
           href="<?= $this->Url->build([
               'prefix' => 'Admin',
               'controller' => 'SelectionPhotos',
               'action' => 'index',
               'album',
               $album->id,
           ]) ?>">
            Choisir les photos
        </a>
    </div>
<?php endif; ?>
