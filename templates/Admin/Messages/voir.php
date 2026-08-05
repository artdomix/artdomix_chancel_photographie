<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 */

$sujet = $message->demande->sujet ?? $message->demande->nom ?? null;
?>
<div class="mb-8">
    <a class="lien-souligne text-sm text-gris"
       href="<?= $this->Url->build(['action' => 'index']) ?>">← Messages</a>
    <h1 class="mt-2 text-2xl"><?= h($message->sujet ?: 'Message de ' . $message->nom) ?></h1>
</div>

<dl class="mb-8 grid max-w-xl grid-cols-[8rem_1fr] gap-y-2 text-sm">
    <dt class="text-gris">Expéditeur</dt>
    <dd><?= h($message->nom) ?></dd>

    <dt class="text-gris">Courriel</dt>
    <dd><a class="lien-souligne" href="mailto:<?= h($message->email) ?>"><?= h($message->email) ?></a></dd>

    <?php if ($message->telephone) : ?>
        <dt class="text-gris">Téléphone</dt>
        <dd><a class="lien-souligne" href="tel:<?= h($message->telephone) ?>"><?= h($message->telephone) ?></a></dd>
    <?php endif; ?>

    <?php if ($sujet !== null) : ?>
        <dt class="text-gris">Type de demande</dt>
        <dd><?= h($sujet) ?></dd>
    <?php endif; ?>

    <dt class="text-gris">Reçu le</dt>
    <dd><?= h($message->created?->format('d/m/Y à H:i')) ?></dd>
</dl>

<?php // nl2br sur le texte échappé : le contenu vient d'un inconnu, jamais de HTML. ?>
<div class="max-w-2xl border-l-2 border-white/10 pl-6 leading-relaxed">
    <?= nl2br(h($message->contenu)) ?>
</div>

<div class="mt-10 flex flex-wrap items-center gap-4 text-sm">
    <a class="bg-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre"
       href="mailto:<?= h($message->email) ?>?subject=<?= h(rawurlencode('Re: ' . ($message->sujet ?: 'votre message'))) ?>">
        Répondre
    </a>

    <?= $this->Form->postLink(
        $message->traite ? 'Rouvrir' : 'Marquer comme traité',
        ['action' => 'basculerTraite', $message->id],
        ['class' => 'border border-white/20 px-5 py-2 uppercase tracking-titre hover:border-corail hover:text-corail'],
    ) ?>

    <?= $this->Form->postLink('Supprimer', ['action' => 'supprimer', $message->id], [
        'class' => 'text-gris hover:text-red-400',
        'confirm' => 'Ce message sera définitivement supprimé. Continuer ?',
    ]) ?>
</div>
