<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $moi
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
$label = ['class' => 'mb-2 block text-sm text-gris'];
?>
<h1 class="mb-8 text-2xl">Mon compte</h1>

<?= $this->Form->create($moi, ['class' => 'max-w-lg space-y-5']) ?>
    <div>
        <p class="mb-2 block text-sm text-gris">Adresse électronique</p>
        <p class="border border-white/10 bg-noir-clair px-3 py-2 text-gris"><?= h($moi->email) ?></p>
        <p class="mt-1 text-xs text-gris">
            C'est votre identifiant de connexion. Pour en changer, écrivez au
            photographe depuis la page contact.
        </p>
    </div>

    <?= $this->Form->control('prenom', ['label' => ['text' => 'Prénom'] + $label, 'class' => $champ]) ?>
    <?= $this->Form->control('nom', ['label' => $label, 'class' => $champ]) ?>
    <?= $this->Form->control('societe', ['label' => ['text' => 'Société'] + $label, 'class' => $champ]) ?>
    <?= $this->Form->control('telephone', ['label' => ['text' => 'Téléphone'] + $label, 'class' => $champ]) ?>

    <?= $this->Form->button('Enregistrer', [
        'class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
    ]) ?>
<?= $this->Form->end() ?>

<div class="mt-10 max-w-lg border-t border-white/10 pt-6">
    <a class="lien-souligne text-sm"
       href="<?= $this->Url->build(['action' => 'motDePasse']) ?>">Changer mon mot de passe</a>
    <?php if ($moi->derniere_connexion !== null) : ?>
        <p class="mt-4 text-xs text-gris">
            Dernière connexion le <?= h($moi->derniere_connexion->format('d/m/Y à H:i')) ?>.
        </p>
    <?php endif; ?>
</div>
