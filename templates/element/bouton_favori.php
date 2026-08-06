<?php
/**
 * Bouton de sélection d'une photo, remplacé sur place par htmx.
 *
 * L'URL de bascule est passée par l'appelant : une galerie s'atteint par deux
 * chemins — le lien de partage et l'espace membre — qui n'ont ni la même route
 * ni les mêmes conditions d'entrée. Coder l'une des deux ici casserait l'autre.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Galerie $galerie
 * @var \App\Model\Entity\Photo $photo
 * @var bool $actif
 * @var array<string, mixed>|string|null $url
 */

// Par défaut, la route du lien de partage : c'est par là qu'arrive un client
// sans compte, donc le cas le plus fréquent.
$url ??= '/g/favori/' . $galerie->share_token . '/' . $photo->id;
?>
<button type="button"
        id="favori-<?= h((string)$photo->id) ?>"
        hx-post="<?= h($this->Url->build($url)) ?>"
        hx-target="this"
        hx-swap="outerHTML"
        hx-headers='{"X-CSRF-Token": "<?= h($this->getRequest()->getAttribute('csrfToken')) ?>"}'
        aria-pressed="<?= $actif ? 'true' : 'false' ?>"
        class="mt-2 flex w-full items-center justify-center gap-2 border px-3 py-2 text-xs uppercase tracking-titre transition-colors <?= $actif
            ? 'border-corail bg-corail text-noir'
            : 'border-white/20 text-gris hover:border-corail hover:text-corail' ?>">
    <span aria-hidden="true"><?= $actif ? '★' : '☆' ?></span>
    <?= $actif ? 'Retenue' : 'Retenir' ?>
</button>
