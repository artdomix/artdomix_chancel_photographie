<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Moodboard $moodboard
 */
?>
<section class="mx-auto flex min-h-[60vh] max-w-sm flex-col justify-center px-6">
    <h1 class="mb-2 text-center text-xl">Sélection protégée</h1>
    <p class="mb-8 text-center text-sm text-gris">
        Saisissez le mot de passe qui vous a été communiqué.
    </p>

    <?= $this->Form->create(null, [
        'url' => ['action' => 'deverrouiller', $moodboard->share_token],
        'class' => 'space-y-4',
    ]) ?>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => ['text' => 'Mot de passe', 'class' => 'mb-2 block text-sm text-gris'],
            'required' => true,
            'autofocus' => true,
            'class' => 'w-full border border-white/20 bg-noir-clair px-4 py-3 focus:border-corail',
        ]) ?>
        <?= $this->Form->button('Accéder', [
            'class' => 'w-full bg-corail px-4 py-3 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
        ]) ?>
    <?= $this->Form->end() ?>
</section>
