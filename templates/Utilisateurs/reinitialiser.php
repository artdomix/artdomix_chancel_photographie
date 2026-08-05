<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $utilisateur
 */

$this->assign('title', 'Nouveau mot de passe');
?>
<h1 class="mb-6 text-center text-lg">Nouveau mot de passe</h1>

<?= $this->Form->create($utilisateur, ['class' => 'space-y-4']) ?>
    <?= $this->Form->control('password', [
        'type' => 'password',
        'label' => ['text' => 'Mot de passe (12 caractères minimum)', 'class' => 'mb-1 block text-sm text-gris'],
        'required' => true,
        'autofocus' => true,
        'autocomplete' => 'new-password',
        'value' => '',
        'class' => 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail',
    ]) ?>
    <?= $this->Form->button('Enregistrer', [
        'class' => 'w-full bg-corail px-4 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
    ]) ?>
<?= $this->Form->end() ?>
