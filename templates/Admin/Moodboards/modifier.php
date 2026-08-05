<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Moodboard $moodboard
 * @var array<string, string> $themes
 * @var array<string, string> $visibilites
 * @var array $membres
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
$label = ['class' => 'mb-2 block text-sm text-gris'];
?>
<h1 class="mb-8 text-2xl"><?= $moodboard->isNew() ? 'Nouveau moodboard' : 'Modifier le moodboard' ?></h1>

<?= $this->Form->create($moodboard, ['class' => 'max-w-xl space-y-5']) ?>
    <?= $this->Form->control('titre', ['label' => $label, 'class' => $champ]) ?>
    <?= $this->Form->control('description', ['type' => 'textarea', 'rows' => 3, 'label' => $label, 'class' => $champ]) ?>
    <?= $this->Form->control('theme', ['type' => 'select', 'options' => $themes, 'label' => ['text' => 'Thème d\'animation'] + $label, 'class' => $champ]) ?>
    <?= $this->Form->control('visibilite', [
        'type' => 'select',
        'options' => $visibilites,
        'label' => $label,
        'class' => $champ,
    ]) ?>
    <?= $this->Form->control('destinataire_id', ['type' => 'select', 'options' => $membres, 'empty' => '— Aucun —', 'label' => ['text' => 'Membre destinataire'] + $label, 'class' => $champ]) ?>

    <div>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'value' => '',
            'label' => ['text' => 'Mot de passe'] + $label,
            'class' => $champ,
        ]) ?>
        <p class="mt-1 text-xs text-gris">
            <?= $moodboard->estProtege()
                ? 'Un mot de passe est déjà défini. Laissez vide pour le conserver.'
                : 'Laissez vide pour ne pas protéger ce moodboard.' ?>
        </p>
    </div>

    <?= $this->Form->control('expires_at', ['type' => 'datetime-local', 'empty' => true, 'label' => ['text' => 'Expire le (facultatif)'] + $label, 'class' => $champ]) ?>
    <?= $this->Form->control('commentaires_actifs', ['label' => ['text' => 'Autoriser les retours', 'class' => 'text-sm text-gris']]) ?>

    <?= $this->Form->button('Enregistrer', ['class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre']) ?>
<?= $this->Form->end() ?>

<?php if (!$moodboard->isNew()) : ?>
    <p class="mt-8 text-sm text-gris">
        Lien de partage :
        <code class="select-all break-all text-blanc-casse">
            <?= h($this->Url->build('/m/' . $moodboard->share_token, ['fullBase' => true])) ?>
        </code>
    </p>
<?php endif; ?>
