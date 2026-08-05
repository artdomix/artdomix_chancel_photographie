<?php
/**
 * Layout du front public.
 *
 * @var \App\View\AppView $this
 */

$titre = $this->fetch('title') ?: 'Chancel Photographie';
$description = $this->fetch('meta_description')
    ?: 'Photographe en Seine-et-Marne. Portrait, voyage, automobile.';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titre) ?></title>
    <meta name="description" content="<?= h($description) ?>">
    <?= $this->Html->meta('icon') ?>
    <link rel="alternate" type="application/rss+xml" title="Journal — Chancel Photographie"
          href="<?= $this->Url->build('/rss') ?>">
    <?php // Canonique : evite que /portfolio/japon et /portfolio/voyage/japon soient vus comme deux pages distinctes. ?>
    <link rel="canonical" href="<?= h($this->Url->build($this->getRequest()->getPath(), ['fullBase' => true])) ?>">

    <?= $this->Assets->base() ?>
    <?= $this->Assets->front() ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="flex min-h-screen flex-col">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-corail focus:px-4 focus:py-2">
        Aller au contenu
    </a>

    <header class="border-b border-white/10">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <a href="<?= $this->Url->build('/') ?>"
               class="font-display text-xl tracking-titre uppercase">
                Chancel
            </a>

            <button type="button" data-menu-bascule aria-expanded="false"
                    aria-controls="menu-principal"
                    class="md:hidden p-2" aria-label="Ouvrir le menu">
                &#9776;
            </button>

            <nav id="menu-principal" data-menu-panneau hidden
                 class="absolute inset-x-0 top-16 z-40 border-b border-white/10 bg-noir px-6 py-4 md:static md:!block md:border-0 md:bg-transparent md:p-0"
                 aria-label="Navigation principale">
                <ul class="flex flex-col gap-4 font-display text-sm tracking-titre uppercase md:flex-row md:gap-8">
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/portfolio') ?>">Portfolio</a></li>
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/tirages') ?>">Tirages</a></li>
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/livres') ?>">Livres</a></li>
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/expositions') ?>">Expositions</a></li>
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/videos') ?>">Vidéos</a></li>
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/carte') ?>">Carte</a></li>
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/blog') ?>">Blog</a></li>
                    <li><a class="lien-souligne" href="<?= $this->Url->build('/contact') ?>">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenu" class="flex-1">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>

    <footer class="border-t border-white/10 py-8 text-center text-sm text-gris">
        &copy; <?= date('Y') ?> Chancel Photographie
    </footer>
</body>
</html>
