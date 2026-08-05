<?php
/**
 * Retour d'un import, injecté par l'uploader à la fin de chaque fichier.
 *
 * @var \App\View\AppView $this
 * @var bool $reussi
 * @var string $message
 */
?>
<li class="flex items-center gap-3 border-b border-white/5 py-2 text-sm">
    <span class="<?= $reussi ? 'text-corail' : 'text-red-400' ?>" aria-hidden="true">
        <?= $reussi ? '✓' : '✕' ?>
    </span>
    <span class="<?= $reussi ? '' : 'text-red-400' ?>"><?= h($message) ?></span>
</li>
