<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $articles
 */
?>
<?= $this->Seo->partage(['titre' => 'Blog — Chancel Photographie']) ?>

<section class="mx-auto max-w-4xl px-6 py-16">
    <h1 data-anim="titre" class="mb-12 text-3xl md:text-5xl">Blog</h1>

    <?php if (count($articles) === 0) : ?>
        <p class="text-gris">Aucun article publié pour l'instant.</p>
    <?php else : ?>
        <div data-anim="grille" class="space-y-12">
            <?php foreach ($articles as $article) : ?>
                <article data-anim-item class="grid gap-6 md:grid-cols-3">
                    <?php if ($article->photo !== null) : ?>
                        <a class="md:col-span-1" href="<?= $this->Url->build('/blog/' . $article->slug) ?>">
                            <?= $this->Photo->image($article->photo, 'grid', [
                                'class' => 'w-full',
                                'sizes' => '(min-width: 768px) 33vw, 100vw',
                            ]) ?>
                        </a>
                    <?php endif; ?>
                    <div class="<?= $article->photo !== null ? 'md:col-span-2' : 'md:col-span-3' ?>">
                        <time class="text-sm text-gris" datetime="<?= h($article->publie_le?->format('c')) ?>">
                            <?= h($article->publie_le?->format('d/m/Y')) ?>
                        </time>
                        <h2 class="mt-2 text-xl">
                            <a class="hover:text-corail" href="<?= $this->Url->build('/blog/' . $article->slug) ?>">
                                <?= h($article->titre) ?>
                            </a>
                        </h2>
                        <?php if ($article->chapeau) : ?>
                            <p class="mt-3 text-gris"><?= h($article->chapeau) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <nav class="mt-12 flex justify-between text-sm" aria-label="Pagination">
            <?= $this->Paginator->prev('← Précédent', ['class' => 'lien-souligne']) ?>
            <?= $this->Paginator->next('Suivant →', ['class' => 'lien-souligne']) ?>
        </nav>
    <?php endif; ?>
</section>
