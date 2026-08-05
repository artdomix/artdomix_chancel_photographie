<?php
/**
 * Layout du back-office.
 *
 * @var \App\View\AppView $this
 */

$lien = 'block px-4 py-2 text-sm hover:bg-white/5 hover:text-corail';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h($this->fetch('title') ?: 'Administration') ?> — Chancel</title>
    <?= $this->Vite->asset('resources/js/admin.js') ?>
</head>
<body class="min-h-screen bg-noir">
<div class="flex min-h-screen">
    <aside class="w-56 shrink-0 border-r border-white/10">
        <div class="px-4 py-5 font-display text-sm uppercase tracking-titre text-corail">Administration</div>
        <nav aria-label="Navigation du back-office">
            <a class="<?= $lien ?>" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Tableau', 'action' => 'index']) ?>">Tableau de bord</a>
            <a class="<?= $lien ?>" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Photos', 'action' => 'index']) ?>">Photos</a>
            <a class="<?= $lien ?>" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Albums', 'action' => 'index']) ?>">Albums</a>
            <a class="<?= $lien ?>" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Moodboards', 'action' => 'index']) ?>">Moodboards</a>
            <a class="<?= $lien ?>" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Galeries', 'action' => 'index']) ?>">Galeries client</a>
            <a class="<?= $lien ?>" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Messages', 'action' => 'index']) ?>">Messages</a>
        </nav>
        <div class="mt-8 border-t border-white/10 pt-4">
            <a class="<?= $lien ?>" href="<?= $this->Url->build('/') ?>">Voir le site</a>
            <a class="<?= $lien ?>" href="<?= $this->Url->build('/deconnexion') ?>">Déconnexion</a>
        </div>
    </aside>

    <main class="flex-1 px-8 py-8">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
</div>
</body>
</html>
