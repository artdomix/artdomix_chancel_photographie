<?php
/**
 * Layout des pages accessibles par lien de partage (moodboards, galeries).
 *
 * Volontairement dépouillé : pas de menu du site. Le destinataire arrive par un
 * lien direct pour voir une sélection, pas pour naviguer dans le portfolio.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php // Un lien de partage n'a rien à faire dans un moteur de recherche. ?>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= h($this->fetch('title') ?: 'Sélection') ?> — Chancel Photographie</title>
    <?= $this->Vite->asset('resources/js/app.js') ?>
</head>
<body class="flex min-h-screen flex-col">
    <header class="border-b border-white/10">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <span class="font-display text-lg uppercase tracking-titre">Chancel</span>
            <a class="text-sm text-gris hover:text-corail" href="<?= $this->Url->build('/') ?>">
                Voir le site
            </a>
        </div>
    </header>

    <main class="flex-1">
        <div class="mx-auto max-w-7xl px-6 pt-6">
            <?= $this->Flash->render() ?>
        </div>
        <?= $this->fetch('content') ?>
    </main>

    <footer class="border-t border-white/10 py-6 text-center text-sm text-gris">
        &copy; <?= date('Y') ?> Chancel Photographie — sélection privée, merci de ne pas diffuser.
    </footer>
</body>
</html>
