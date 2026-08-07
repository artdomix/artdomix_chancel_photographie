<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $photos
 * @var string $recherche
 * @var array<string, array<string, string>> $destinations
 */
?>
<div class="mb-8 flex items-center justify-between">
    <h1 class="text-2xl">Photos</h1>
    <a class="bg-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre"
       href="<?= $this->Url->build(['action' => 'ajouter']) ?>">
        Importer
    </a>
</div>

<form class="mb-6 max-w-md" role="search"
      hx-get="<?= $this->Url->build(['action' => 'index']) ?>"
      hx-trigger="input changed delay:350ms from:find input"
      hx-target="#liste-photos"
      hx-select="#liste-photos"
      hx-swap="outerHTML">
    <label class="sr-only" for="q">Rechercher une photo</label>
    <input id="q" name="q" type="search" value="<?= h($recherche) ?>" placeholder="Rechercher…"
           class="w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail">
</form>

<?= $this->Form->create(null, ['url' => ['action' => 'enMasse'], 'id' => 'form-masse']) ?>
    <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <?= $this->Form->control('action_masse', [
            'type' => 'select',
            'options' => [
                'activer' => 'Mettre en ligne',
                'desactiver' => 'Masquer',
                'tagger' => 'Ajouter des tags',
                'rattacher' => 'Ajouter à…',
            ],
            'empty' => 'Action groupée…',
            'label' => false,
            'class' => 'border border-white/20 bg-noir-clair px-3 py-2',
        ]) ?>

        <?php
        // Un seul menu réunit albums, moodboards et galeries : `optgroup` les
        // distingue sans imposer un choix de catégorie préalable. La valeur
        // porte le type et l'identifiant, séparés par deux-points.
        ?>
        <?= $this->Form->control('cible', [
            'type' => 'select',
            'options' => $destinations,
            'empty' => 'Destination…',
            'label' => false,
            'class' => 'border border-white/20 bg-noir-clair px-3 py-2',
        ]) ?>
        <?= $this->Form->button('Appliquer', [
            'class' => 'border border-white/20 px-4 py-2 uppercase tracking-titre hover:border-corail hover:text-corail',
        ]) ?>
    </div>

    <?= $this->element('admin/liste_photos', ['photos' => $photos]) ?>
<?= $this->Form->end() ?>
