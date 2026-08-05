<?php
/**
 * Layout des pages d'erreur (404, 500).
 *
 * Le squelette CakePHP renvoyait ici vers `normalize.min` / `milligram.min` /
 * `cake`, feuilles absentes de ce projet : une erreur s'affichait donc sans
 * aucun style. On reprend le thème du site, sans le menu — une page d'erreur n'a
 * pas à charger GSAP ni htmx.
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
    <title><?= h($this->fetch('title') ?: 'Erreur') ?> — Chancel Photographie</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Assets->base() ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="flex min-h-screen flex-col">
    <header class="border-b border-white/10">
        <div class="mx-auto w-full max-w-3xl px-6 py-5">
            <a href="<?= $this->Url->build('/') ?>"
               class="font-display text-xl uppercase tracking-titre">Chancel</a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-3xl flex-1 px-6 py-16">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>

        <p class="mt-10">
            <a class="lien-souligne" href="<?= $this->Url->build('/') ?>">Retour à l'accueil</a>
        </p>
    </main>
</body>
</html>
