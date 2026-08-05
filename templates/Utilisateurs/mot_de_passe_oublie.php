<?php
/**
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Mot de passe oublié');
?>
<h1 class="mb-6 text-center text-lg">Mot de passe oublié</h1>

<p class="mb-6 text-sm text-gris">
    Indiquez votre adresse : si un compte y correspond, vous recevrez un lien de
    réinitialisation valable deux heures.
</p>

<?= $this->Form->create(null, ['class' => 'space-y-4']) ?>
    <?= $this->Form->control('email', [
        'type' => 'email',
        'label' => ['text' => 'Adresse e-mail', 'class' => 'mb-1 block text-sm text-gris'],
        'required' => true,
        'autofocus' => true,
        'class' => 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail',
    ]) ?>
    <?= $this->Form->button('Envoyer le lien', [
        'class' => 'w-full bg-corail px-4 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
    ]) ?>
<?= $this->Form->end() ?>

<p class="mt-6 text-center text-sm">
    <a class="lien-souligne text-gris" href="<?= $this->Url->build('/connexion') ?>">Retour à la connexion</a>
</p>
