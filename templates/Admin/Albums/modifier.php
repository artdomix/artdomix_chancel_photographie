<?php

use App\Model\Enum\VisibiliteAlbum;

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Album $album
 * @var array $parents
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
?>
<h1 class="mb-8 text-2xl">Modifier « <?= h($album->nom) ?> »</h1>

<?= $this->Form->create($album, ['class' => 'max-w-xl space-y-5']) ?>
    <?= $this->Form->control('nom', ['label' => ['class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('description', ['type' => 'textarea', 'rows' => 3, 'label' => ['class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('parent_id', ['type' => 'select', 'options' => $parents, 'empty' => '— Racine —', 'label' => ['text' => 'Album parent', 'class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('visibilite', ['type' => 'select', 'options' => VisibiliteAlbum::options(), 'label' => ['class' => 'mb-2 block text-sm text-gris'], 'class' => $champ]) ?>
    <?= $this->Form->control('actif', ['label' => ['text' => 'En ligne', 'class' => 'text-sm text-gris']]) ?>
    <?= $this->Form->button('Enregistrer', ['class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre']) ?>
<?= $this->Form->end() ?>

<h2 class="mb-2 mt-12 text-lg">Ordre des photos</h2>
<p class="mb-4 text-sm text-gris">
    Glissez les vignettes pour les réordonner, ou utilisez les flèches gauche et
    droite après avoir sélectionné une vignette au clavier.
</p>
<p data-tri-etat class="mb-4 text-sm text-corail" role="status" aria-live="polite"></p>

<div data-tri
     data-tri-url="<?= $this->Url->build(['action' => 'ordonner', $album->id]) ?>"
     data-csrf="<?= h($this->getRequest()->getAttribute('csrfToken')) ?>"
     class="grid grid-cols-3 gap-3 md:grid-cols-6">
    <?php foreach ($album->photos as $photo) : ?>
        <div data-tri-item="<?= h((string)$photo->id) ?>" tabindex="0"
             class="cursor-grab focus:outline focus:outline-2 focus:outline-corail">
            <img src="<?= h($this->Photo->url($photo, 'thumb', 'jpeg')) ?>"
                 alt="<?= h((string)($photo->titre ?? '')) ?>" width="160" height="107"
                 loading="lazy" class="w-full object-cover">
        </div>
    <?php endforeach; ?>
</div>
