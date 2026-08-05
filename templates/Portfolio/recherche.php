<?php
/**
 * @var \App\View\AppView $this
 * @var string $terme
 * @var iterable $photos
 * @var iterable $tags
 */
?>
<?= $this->Seo->partage(['titre' => 'Recherche — Chancel Photographie']) ?>

<section class="mx-auto max-w-7xl px-6 py-12">
    <h1 data-anim="titre" class="mb-8 text-3xl md:text-5xl">Recherche</h1>

    <?php
    // `hx-trigger` avec un délai : on attend une pause dans la frappe plutôt que
    // d'envoyer une requête par caractère.
    ?>
    <form class="mb-10 max-w-xl" role="search"
          hx-get="<?= $this->Url->build('/recherche') ?>"
          hx-trigger="input changed delay:350ms from:find input, submit"
          hx-target="#resultats"
          hx-select="#resultats"
          hx-swap="outerHTML"
          hx-push-url="true">
        <label class="mb-2 block text-sm text-gris" for="q">Mot-clé, lieu, boîtier…</label>
        <input id="q" name="q" type="search" value="<?= h($terme) ?>" autocomplete="off"
               class="w-full border border-white/20 bg-noir-clair px-4 py-3 focus:border-corail">
    </form>

    <nav aria-label="Tags" class="mb-10 flex flex-wrap gap-2">
        <?php foreach ($tags as $tag) : ?>
            <a class="border border-white/20 px-3 py-1 text-xs uppercase tracking-titre hover:border-corail hover:text-corail"
               href="<?= $this->Url->build('/tag/' . $tag->slug) ?>">
                <?= h($tag->nom) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?= $this->element('resultats_recherche', ['terme' => $terme, 'photos' => $photos]) ?>
</section>
