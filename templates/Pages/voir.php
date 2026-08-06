<?php
/**
 * Page éditoriale (mentions légales, à propos, conditions de vente…).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Page $page
 */
?>
<?= $this->Seo->partage([
    'titre' => $page->titre . ' — Chancel Photographie',
    'description' => (string)($page->meta_description ?? ''),
    'type' => 'article',
]) ?>

<article class="mx-auto max-w-3xl px-6 py-16">
    <h1 data-anim="titre" class="mb-10 text-3xl md:text-5xl"><?= h($page->titre) ?></h1>

    <?php
    // Contenu saisi en HTML depuis l'administration, donc affiché tel quel. La
    // section n'est accessible qu'aux administrateurs : c'est le photographe qui
    // écrit ces pages, pas un visiteur.
    ?>
    <div class="prose-chancel space-y-4 leading-relaxed">
        <?= $page->contenu ?>
    </div>

    <?php if ($page->modified !== null) : ?>
        <p class="mt-12 text-sm text-gris">
            Dernière mise à jour le <?= h($page->modified->format('d/m/Y')) ?>.
        </p>
    <?php endif; ?>
</article>
