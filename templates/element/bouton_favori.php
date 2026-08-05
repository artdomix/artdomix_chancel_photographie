<?php
/**
 * Bouton de sélection d'une photo, remplacé sur place par htmx.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Galerie $galerie
 * @var \App\Model\Entity\Photo $photo
 * @var bool $actif
 */
?>
<button type="button"
        id="favori-<?= h((string)$photo->id) ?>"
        hx-post="<?= $this->Url->build('/g/favori/' . $galerie->share_token . '/' . $photo->id) ?>"
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
