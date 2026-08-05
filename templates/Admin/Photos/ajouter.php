<?php
/**
 * Import par glisser-déposer.
 *
 * @var \App\View\AppView $this
 */
?>
<h1 class="mb-2 text-2xl">Importer des photos</h1>
<p class="mb-8 max-w-2xl text-sm text-gris">
    Déposez vos fichiers ci-dessous. Chaque photo est envoyée séparément et
    traitée dans la foulée : les vignettes, les formats modernes et les données
    EXIF sont produits automatiquement.
</p>

<div id="zone-depot"
     data-upload
     data-url="<?= $this->Url->build(['action' => 'ajouter']) ?>"
     data-csrf="<?= h($this->getRequest()->getAttribute('csrfToken')) ?>"
     class="flex min-h-56 cursor-pointer flex-col items-center justify-center border-2 border-dashed border-white/20 p-10 text-center transition-colors hover:border-corail">
    <p class="font-display uppercase tracking-titre">Glissez vos photos ici</p>
    <p class="mt-2 text-sm text-gris">ou cliquez pour choisir des fichiers</p>
    <input type="file" data-upload-input multiple accept="image/*" class="sr-only">
</div>

<div class="mt-6">
    <div data-upload-progression class="hidden">
        <div class="h-1 w-full bg-white/10">
            <div data-upload-barre class="h-1 w-0 bg-corail transition-all"></div>
        </div>
        <p data-upload-etat class="mt-2 text-sm text-gris" role="status" aria-live="polite"></p>
    </div>

    <ul data-upload-resultats class="mt-4"></ul>
</div>
