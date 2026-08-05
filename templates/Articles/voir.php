<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Article $article
 * @var \App\Model\Entity\Commentaire $commentaire
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-4 py-3 focus:border-corail';
?>
<?= $this->Seo->partage([
    'titre' => $article->titre . ' — Chancel Photographie',
    'description' => (string)($article->chapeau ?: ''),
    'image' => $article->photo,
    'type' => 'article',
]) ?>
<?= $this->Seo->filAriane([
    ['nom' => 'Blog', 'url' => '/blog'],
    ['nom' => $article->titre, 'url' => '/blog/' . $article->slug],
]) ?>

<article class="mx-auto max-w-3xl px-6 py-12">
    <header class="mb-10">
        <time class="text-sm text-gris" datetime="<?= h($article->publie_le?->format('c')) ?>">
            <?= h($article->publie_le?->format('d/m/Y')) ?>
        </time>
        <h1 class="mt-3 text-3xl md:text-5xl"><?= h($article->titre) ?></h1>
        <?php if ($article->chapeau) : ?>
            <p class="mt-5 text-lg text-gris"><?= h($article->chapeau) ?></p>
        <?php endif; ?>
    </header>

    <?php if ($article->photo !== null) : ?>
        <?= $this->Photo->image($article->photo, 'content', [
            'class' => 'mb-10 w-full',
            'sizes' => '(min-width: 768px) 768px, 100vw',
            'lazy' => false,
        ]) ?>
    <?php endif; ?>

    <?php
    // Le contenu vient de l'éditeur riche de l'admin, alimenté uniquement par le
    // photographe : il est volontairement rendu tel quel. Un commentaire de
    // visiteur, lui, est toujours échappé.
    ?>
    <div class="prose-photo space-y-4 leading-relaxed">
        <?= $article->contenu ?>
    </div>
</article>

<section class="mx-auto max-w-3xl border-t border-white/10 px-6 py-12">
    <h2 class="mb-8 text-xl">
        Commentaires<?= count($article->commentaires) > 0 ? ' (' . count($article->commentaires) . ')' : '' ?>
    </h2>

    <?php if (count($article->commentaires) === 0) : ?>
        <p class="mb-10 text-sm text-gris">Aucun commentaire pour l'instant.</p>
    <?php else : ?>
        <ul class="mb-12 space-y-6">
            <?php foreach ($article->commentaires as $c) : ?>
                <li class="border-l-2 border-white/10 pl-4">
                    <p class="text-sm text-gris">
                        <?= h($c->auteur ?: 'Anonyme') ?> — <?= h($c->created?->format('d/m/Y')) ?>
                    </p>
                    <p class="mt-2"><?= nl2br(h($c->contenu)) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?= $this->Form->create($commentaire, [
        'url' => ['action' => 'commenter', $article->slug],
        'class' => 'space-y-4',
    ]) ?>
        <div class="grid gap-4 md:grid-cols-2">
            <?= $this->Form->control('auteur', [
                'label' => ['text' => 'Votre nom', 'class' => 'mb-2 block text-sm text-gris'],
                'required' => true,
                'class' => $champ,
            ]) ?>
            <?= $this->Form->control('email', [
                'type' => 'email',
                'label' => ['text' => 'Votre e-mail (non publié)', 'class' => 'mb-2 block text-sm text-gris'],
                'class' => $champ,
            ]) ?>
        </div>
        <?= $this->Form->control('contenu', [
            'type' => 'textarea',
            'rows' => 5,
            'label' => ['text' => 'Votre commentaire', 'class' => 'mb-2 block text-sm text-gris'],
            'required' => true,
            'class' => $champ,
        ]) ?>
        <?= $this->Form->button('Publier', [
            'class' => 'border border-corail px-6 py-3 font-display text-sm uppercase tracking-titre text-corail hover:bg-corail hover:text-noir',
        ]) ?>
    <?= $this->Form->end() ?>
</section>
