<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $moi
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
$label = ['class' => 'mb-2 block text-sm text-gris'];
?>
<nav class="mb-8 text-sm text-gris" aria-label="Fil d'Ariane">
    <a class="lien-souligne" href="<?= $this->Url->build(['action' => 'index']) ?>">Mon compte</a>
    <span class="mx-2">/</span>
    <span aria-current="page" class="text-blanc-casse">Mot de passe</span>
</nav>

<h1 class="mb-8 text-2xl">Changer mon mot de passe</h1>

<?php
// Formulaire sans entité : les deux champs ne se rangent pas au même endroit —
// l'ancien mot de passe n'est qu'une vérification, il n'est jamais enregistré.
?>
<?= $this->Form->create(null, ['class' => 'max-w-md space-y-5']) ?>
    <?= $this->Form->control('mot_de_passe_actuel', [
        'type' => 'password',
        'label' => ['text' => 'Mot de passe actuel'] + $label,
        'required' => true,
        'autocomplete' => 'current-password',
        'class' => $champ,
    ]) ?>

    <div>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => ['text' => 'Nouveau mot de passe'] + $label,
            'required' => true,
            'autocomplete' => 'new-password',
            'class' => $champ,
        ]) ?>
        <p class="mt-1 text-xs text-gris">12 caractères minimum.</p>
    </div>

    <div class="flex items-center gap-6">
        <?= $this->Form->button('Changer', [
            'class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
        ]) ?>
        <a class="text-sm text-gris hover:text-corail"
           href="<?= $this->Url->build(['action' => 'index']) ?>">Annuler</a>
    </div>
<?= $this->Form->end() ?>
