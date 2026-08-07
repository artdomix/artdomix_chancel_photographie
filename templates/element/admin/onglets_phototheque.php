<?php
/**
 * Onglets de filtrage de la photothèque, avec leur compteur.
 *
 * Rendus aussi hors bande après une bascule : c'est le seul moyen de garder les
 * chiffres justes sans recharger toute la page. `hx-swap-oob` demande à htmx de
 * remplacer l'élément de même identifiant, où qu'il soit dans le document.
 *
 * @var \App\View\AppView $this
 * @var \App\Service\Association\Liaison $liaison
 * @var \Cake\Datasource\EntityInterface $cible
 * @var string $filtre
 * @var array<string, int> $compteurs
 * @var string $recherche
 * @var bool $horsBande
 */

$horsBande ??= false;
$recherche ??= '';

$libelles = [
    'toutes' => 'Toutes',
    'libres' => 'Non rattachées',
    'liees' => 'Déjà rattachées',
];

$urlOnglet = fn(string $cle): string => $this->Url->build([
    'prefix' => 'Admin',
    'controller' => 'SelectionPhotos',
    'action' => 'index',
    $liaison->type,
    $cible->id,
    '?' => array_filter(['q' => $recherche, 'filtre' => $cle], fn($v) => $v !== '' && $v !== 'toutes'),
]);
?>
<div id="onglets-phototheque"<?= $horsBande ? ' hx-swap-oob="true"' : '' ?>
     class="mb-4 flex flex-wrap gap-4 text-sm">
    <?php foreach ($libelles as $cle => $libelle) : ?>
        <?php $actif = $filtre === $cle; ?>
        <a class="<?= $actif ? 'border-b-2 border-corail pb-1 text-corail' : 'pb-1 text-gris hover:text-corail' ?>"
           <?= $actif ? 'aria-current="page"' : '' ?>
           hx-get="<?= h($urlOnglet($cle)) ?>"
           hx-target="#phototheque"
           hx-select="#phototheque"
           hx-swap="outerHTML"
           href="<?= h($urlOnglet($cle)) ?>">
            <?= h($libelle) ?>
            <span class="ml-1 text-xs"><?= h((string)($compteurs[$cle] ?? 0)) ?></span>
        </a>
    <?php endforeach; ?>
</div>
