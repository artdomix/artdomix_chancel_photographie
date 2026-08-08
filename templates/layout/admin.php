<?php
/**
 * Layout du back-office.
 *
 * Le menu est décrit en données plutôt qu'en balisage répété : c'est lui qui
 * fait office d'inventaire des sections, et une entrée ajoutée ici est le seul
 * geste nécessaire pour qu'une nouvelle section soit atteignable.
 *
 * @var \App\View\AppView $this
 */

$sections = [
    '' => [
        'Tableau' => 'Tableau de bord',
    ],
    'Photothèque' => [
        'Photos' => 'Photos',
        'Albums' => 'Albums',
        'Tags' => 'Tags',
    ],
    'Partage' => [
        'Moodboards' => 'Moodboards',
        'Galeries' => 'Galeries client',
    ],
    'Publications' => [
        'Articles' => 'Articles',
        'Commentaires' => 'Commentaires',
        'Pages' => 'Pages',
    ],
    'Catalogue' => [
        'Livres' => 'Livres',
        'Tirages' => 'Tirages',
        'Typetirages' => 'Types de tirage',
        'Expositions' => 'Expositions',
        'Videos' => 'Vidéos',
        'Typevideos' => 'Types de vidéo',
    ],
    'Séances' => [
        'Modeles' => 'Modèles',
        'Shootings' => 'Shootings',
    ],
    'Administration' => [
        'Messages' => 'Messages',
        'Demandes' => 'Types de demande',
        'Users' => 'Comptes',
        'Config' => 'Réglages',
    ],
];

$controleurCourant = $this->getRequest()->getParam('controller');
$lien = 'block px-4 py-1.5 text-sm hover:bg-white/5 hover:text-corail';
$lienActif = 'block border-l-2 border-corail bg-white/5 px-4 py-1.5 text-sm text-corail';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h($this->fetch('title') ?: 'Administration') ?> — Chancel</title>
    <?= $this->Assets->base() ?>
    <?= $this->Assets->admin() ?>
    <?= $this->fetch('script') ?>
</head>
<body class="min-h-screen bg-noir">
<div class="flex min-h-screen">
    <aside class="w-56 shrink-0 border-r border-white/10">
        <div class="px-4 py-5 font-display text-sm uppercase tracking-titre text-corail">Administration</div>

        <nav aria-label="Navigation du back-office" class="pb-8">
            <?php foreach ($sections as $intitule => $entrees) : ?>
                <?php if ($intitule !== '') : ?>
                    <p class="px-4 pb-1 pt-5 text-xs uppercase tracking-titre text-gris">
                        <?= h($intitule) ?>
                    </p>
                <?php endif; ?>

                <?php foreach ($entrees as $controleur => $libelle) : ?>
                    <?php $actif = $controleurCourant === $controleur; ?>
                    <a class="<?= $actif ? $lienActif : $lien ?>"
                       <?= $actif ? 'aria-current="page"' : '' ?>
                       href="<?= $this->Url->build([
                           'prefix' => 'Admin',
                           'controller' => $controleur,
                           'action' => 'index',
                       ]) ?>">
                        <?= h($libelle) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="border-t border-white/10 py-4">
            <a class="<?= $lien ?>" href="<?= $this->Url->build('/') ?>">Voir le site</a>
            <a class="<?= $lien ?>" href="<?= $this->Url->build('/deconnexion') ?>">Déconnexion</a>
        </div>
    </aside>

    <main class="flex-1 px-8 py-8">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
</div>

<?php
// `Form->postLink()` fabrique un formulaire par lien. Quand le lien se trouve à
// l'intérieur d'un autre formulaire — ce que le HTML interdit — l'option
// `block` met ce formulaire en réserve, et c'est ici qu'il doit ressortir.
// Sans cette ligne, le lien s'affiche mais ne déclenche rien : c'est ce qui
// rendait « Définir en couverture » inopérant.
?>
<?= $this->fetch('postLink') ?>
</body>
</html>
