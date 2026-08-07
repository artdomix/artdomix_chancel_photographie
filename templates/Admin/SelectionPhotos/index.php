<?php
/**
 * Écran de rattachement des photos, commun aux albums, moodboards et galeries.
 *
 * @var \App\View\AppView $this
 * @var \App\Service\Association\Liaison $liaison
 * @var \Cake\Datasource\EntityInterface $cible
 * @var iterable $phototheque
 * @var list<int> $liees
 * @var string $recherche
 * @var string $filtre
 * @var array<string, int> $compteurs
 */

$nom = $cible->get('nom') ?? $cible->get('titre');
$retour = ['prefix' => 'Admin', 'controller' => $liaison->controleur, 'action' => 'modifier', $cible->id];
$urlBase = ['prefix' => 'Admin', 'controller' => 'SelectionPhotos'];
$couverture = $liaison->couverture === null ? null : $cible->get($liaison->couverture);
?>
<div class="mb-8">
    <a class="lien-souligne text-sm text-gris" href="<?= $this->Url->build($retour) ?>">
        ← <?= h(ucfirst($liaison->libelle)) ?> « <?= h((string)$nom) ?> »
    </a>
    <h1 class="mt-2 text-2xl">Photos du <?= h($liaison->libelle) ?></h1>
</div>

<section class="mb-12">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-lg">
            Sélection — <?= h((string)count($cible->photos)) ?> photo(s)
        </h2>

        <?php if (count($cible->photos) > 0) : ?>
            <?= $this->Form->postLink('Tout retirer', $urlBase + ['action' => 'retirer', $liaison->type, $cible->id], [
                'class' => 'text-sm text-gris hover:text-red-400',
                'confirm' => sprintf(
                    'Toutes les photos seront retirées de ce %s. Les photos elles-mêmes sont conservées. Continuer ?',
                    $liaison->libelle,
                ),
            ]) ?>
        <?php endif; ?>
    </div>

    <?php if (count($cible->photos) === 0) : ?>
        <p class="border border-dashed border-white/15 py-12 text-center text-gris">
            Aucune photo pour l'instant. Choisissez-en dans la photothèque ci-dessous.
        </p>
    <?php else : ?>
        <p class="mb-4 text-sm text-gris">
            Glissez les vignettes pour changer l'ordre d'affichage, ou utilisez les
            flèches gauche et droite après en avoir sélectionné une au clavier.
        </p>
        <p data-tri-etat class="mb-4 text-sm text-corail" role="status" aria-live="polite"></p>

        <?= $this->Form->create(null, [
            'url' => $urlBase + ['action' => 'retirer', $liaison->type, $cible->id],
        ]) ?>
            <div data-tri
                 data-tri-url="<?= $this->Url->build($urlBase + ['action' => 'ordonner', $liaison->type, $cible->id]) ?>"
                 data-csrf="<?= h($this->getRequest()->getAttribute('csrfToken')) ?>"
                 class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <?php foreach ($cible->photos as $photo) : ?>
                    <?php $estCouverture = $couverture !== null && (int)$couverture === (int)$photo->id; ?>
                    <div data-tri-item="<?= h((string)$photo->id) ?>" tabindex="0"
                         class="cursor-grab border <?= $estCouverture ? 'border-corail' : 'border-white/10' ?>
                                focus:outline focus:outline-2 focus:outline-corail">
                        <img src="<?= h($this->Photo->url($photo, 'thumb', 'jpeg')) ?>"
                             alt="" width="320" height="213" loading="lazy" class="w-full object-cover">

                        <div class="space-y-2 p-2 text-xs">
                            <label class="flex items-center gap-2 text-gris">
                                <input type="checkbox" name="ids[]" value="<?= h((string)$photo->id) ?>">
                                <span class="truncate"><?= h($photo->titre ?? $photo->fichier) ?></span>
                            </label>

                            <?php if ($liaison->couverture !== null) : ?>
                                <?php if ($estCouverture) : ?>
                                    <p class="text-corail">Photo de couverture</p>
                                <?php else : ?>
                                    <?= $this->Form->postLink('Définir en couverture', $urlBase + [
                                        'action' => 'couverture', $liaison->type, $cible->id, $photo->id,
                                    ], ['class' => 'text-gris hover:text-corail', 'block' => true]) ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($liaison->estAnnotable()) : ?>
                                <?= $this->element('admin/annotation_photo', [
                                    'liaison' => $liaison,
                                    'cible' => $cible,
                                    'photo' => $photo,
                                ]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                <?= $this->Form->button('Retirer les photos cochées', [
                    'class' => 'border border-white/20 px-4 py-2 uppercase tracking-titre hover:border-corail hover:text-corail',
                ]) ?>
                <?php if ($couverture !== null) : ?>
                    <?= $this->Form->postLink('Retirer la couverture', $urlBase + [
                        'action' => 'couverture', $liaison->type, $cible->id, 'aucune',
                    ], ['class' => 'text-gris hover:text-corail']) ?>
                <?php endif; ?>
            </div>
        <?= $this->Form->end() ?>
    <?php endif; ?>
</section>

<section class="border-t border-white/10 pt-8">
    <h2 class="mb-4 text-lg">Photothèque</h2>

    <?php
    // Seul le panneau de la photothèque est remplacé : la sélection du haut
    // reste à l'écran pendant qu'on cherche.
    ?>
    <?= $this->element('admin/onglets_phototheque') ?>

    <form class="mb-6 max-w-md" role="search"
          hx-get="<?= $this->Url->build($urlBase + ['action' => 'index', $liaison->type, $cible->id]) ?>"
          hx-trigger="input changed delay:350ms from:find input"
          hx-target="#phototheque"
          hx-select="#phototheque"
          hx-swap="outerHTML">
        <label class="sr-only" for="q">Rechercher une photo</label>
        <input id="q" name="q" type="search" value="<?= h($recherche) ?>" placeholder="Rechercher…"
               class="w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail">
        <?php
        // Le filtre courant repart avec la recherche : sans lui, taper un mot
        // ramènerait le photographe sur « Toutes » sans qu'il l'ait demandé.
        ?>
        <input type="hidden" name="filtre" value="<?= h($filtre) ?>">
    </form>

    <?= $this->element('admin/phototheque') ?>
</section>
