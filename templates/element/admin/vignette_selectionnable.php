<?php
/**
 * Vignette de la photothèque, avec son bouton de rattachement.
 *
 * Renvoyée telle quelle par htmx après une bascule : c'est elle qui remplace
 * l'ancienne dans la grille, l'état affiché vient donc toujours de la base et
 * jamais d'une supposition du navigateur.
 *
 * @var \App\View\AppView $this
 * @var \App\Service\Association\Liaison $liaison
 * @var \Cake\Datasource\EntityInterface $cible
 * @var \App\Model\Entity\Photo $photo
 * @var bool $liee
 * @var array<string, int>|null $compteurs
 * @var string|null $filtre
 * @var bool|null $rafraichirOnglets
 */

$url = $this->Url->build([
    'prefix' => 'Admin',
    'controller' => 'SelectionPhotos',
    'action' => 'basculer',
    $liaison->type,
    $cible->id,
    $photo->id,
]);
?>
<li id="vignette-<?= h((string)$photo->id) ?>"
    class="border <?= $liee ? 'border-corail' : 'border-white/10' ?>">
    <button type="button"
            hx-post="<?= h($url) ?>"
            hx-target="#vignette-<?= h((string)$photo->id) ?>"
            hx-swap="outerHTML"
            hx-headers='{"X-CSRF-Token": "<?= h($this->getRequest()->getAttribute('csrfToken')) ?>"}'
            aria-pressed="<?= $liee ? 'true' : 'false' ?>"
            class="block w-full text-left">
        <span class="relative block">
            <img src="<?= h($this->Photo->url($photo, 'thumb', 'jpeg')) ?>"
                 alt="" width="320" height="213" loading="lazy"
                 class="w-full object-cover <?= $liee ? '' : 'opacity-70' ?>">
            <?php if ($liee) : ?>
                <span aria-hidden="true"
                      class="absolute right-1 top-1 bg-corail px-1.5 text-xs text-noir">✓</span>
            <?php endif; ?>
        </span>
        <span class="block truncate px-2 py-1 text-xs <?= $liee ? 'text-corail' : 'text-gris' ?>">
            <?= $liee ? 'Retirer' : 'Ajouter' ?>
            — <?= h($photo->titre ?? $photo->fichier) ?>
        </span>
    </button>
</li>

<?php // Drapeau explicite, et non `isset($compteurs)` : la page d'index définit
      // aussi cette variable, et chaque vignette de la photothèque rendrait alors
      // son propre bloc d'onglets — autant d'éléments partageant le même
      // identifiant, ce que htmx comme le HTML interdisent. ?>
<?php if (!empty($rafraichirOnglets)) : ?>
    <?php
    // Réponse à une bascule : les compteurs des onglets viennent de changer, ils
    // repartent avec la vignette plutôt que d'attendre un rechargement.
    ?>
    <?= $this->element('admin/onglets_phototheque', [
        'liaison' => $liaison,
        'cible' => $cible,
        'filtre' => $filtre ?? 'toutes',
        'compteurs' => $compteurs,
        'horsBande' => true,
    ]) ?>
<?php endif; ?>
