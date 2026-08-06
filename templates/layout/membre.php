<?php
/**
 * Layout de l'espace membre (clients).
 *
 * @var \App\View\AppView $this
 */

$identite = $this->getRequest()->getAttribute('identity');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h($this->fetch('title') ?: 'Mon espace') ?> — Chancel Photographie</title>
    <?= $this->Assets->base() ?>
    <?= $this->Assets->front() ?>
    <?= $this->fetch('script') ?>
</head>
<body class="flex min-h-screen flex-col">
    <header class="border-b border-white/10">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <a href="<?= $this->Url->build(['prefix' => 'Membre', 'controller' => 'Tableau', 'action' => 'index']) ?>"
               class="font-display text-xl uppercase tracking-titre">
                Chancel
            </a>
            <nav class="flex items-center gap-6 text-sm" aria-label="Navigation de l'espace membre">
                <a class="lien-souligne"
                   href="<?= $this->Url->build(['prefix' => 'Membre', 'controller' => 'Tableau', 'action' => 'index']) ?>">
                    Mes livraisons
                </a>
                <a class="lien-souligne"
                   href="<?= $this->Url->build(['prefix' => 'Membre', 'controller' => 'Compte', 'action' => 'index']) ?>">
                    <?= $identite !== null ? h($identite->getOriginalData()->nom_complet) : 'Mon compte' ?>
                </a>
                <a class="lien-souligne" href="<?= $this->Url->build('/deconnexion') ?>">Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>

    <footer class="border-t border-white/10 py-6 text-center text-sm text-gris">
        &copy; <?= date('Y') ?> Chancel Photographie
    </footer>
</body>
</html>
