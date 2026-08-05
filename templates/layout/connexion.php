<?php
/**
 * Layout des pages de compte (connexion, mot de passe oublié).
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h($this->fetch('title') ?: 'Connexion') ?> — Chancel Photographie</title>
    <?= $this->Html->meta('csrfToken', $this->request->getAttribute('csrfToken')) ?>
    <?= $this->Vite->asset('resources/js/app.js') ?>
</head>
<body class="flex min-h-screen items-center justify-center px-6">
    <main class="w-full max-w-sm">
        <a href="<?= $this->Url->build('/') ?>"
           class="mb-10 block text-center font-display text-2xl uppercase tracking-titre">
            Chancel
        </a>
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
</body>
</html>
