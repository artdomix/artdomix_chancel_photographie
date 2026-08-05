<?php
/**
 * Accueil provisoire — remplacée à l'étape « Front public » par la vraie page
 * (héros GSAP, albums mis en avant, derniers articles). Elle existe surtout pour
 * valider que le layout et la chaîne d'assets Vite fonctionnent.
 *
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Chancel Photographie');
?>
<section class="mx-auto max-w-7xl px-6 py-24">
    <h1 data-anim="titre" class="text-4xl md:text-6xl">
        Chancel Photographie
    </h1>

    <p data-anim class="mt-6 max-w-xl text-gris">
        Portrait, voyage, automobile. Le nouveau site est en cours de construction.
    </p>

    <a href="<?= $this->Url->build('/portfolio') ?>"
       class="mt-10 inline-block border border-corail px-6 py-3 font-display text-sm uppercase tracking-titre text-corail transition-colors hover:bg-corail hover:text-noir">
        Voir le portfolio
    </a>
</section>
