<?php
/**
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Connexion');
?>
<h1 class="mb-6 text-center text-lg">Connexion</h1>

<?= $this->Form->create(null, ['class' => 'space-y-4']) ?>
    <div>
        <?= $this->Form->control('email', [
            'type' => 'email',
            'label' => ['text' => 'Adresse e-mail', 'class' => 'mb-1 block text-sm text-gris'],
            'required' => true,
            'autofocus' => true,
            'autocomplete' => 'username',
            'class' => 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail',
        ]) ?>
    </div>
    <div>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => ['text' => 'Mot de passe', 'class' => 'mb-1 block text-sm text-gris'],
            'required' => true,
            'autocomplete' => 'current-password',
            'class' => 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail',
        ]) ?>
    </div>
    <?= $this->Form->button('Se connecter', [
        'class' => 'w-full bg-corail px-4 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
    ]) ?>
<?= $this->Form->end() ?>

<p class="mt-6 text-center text-sm">
    <a class="lien-souligne text-gris" href="<?= $this->Url->build('/mot-de-passe-oublie') ?>">
        Mot de passe oublié ?
    </a>
</p>
