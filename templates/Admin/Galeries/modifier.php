<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Galerie $galerie
 * @var array $clients
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
$label = ['class' => 'mb-2 block text-sm text-gris'];
?>
<h1 class="mb-8 text-2xl">
    <?= $galerie->isNew() ? 'Nouvelle galerie client' : 'Modifier « ' . h($galerie->nom) . ' »' ?>
</h1>

<?= $this->Form->create($galerie, ['class' => 'max-w-xl space-y-5']) ?>
    <?= $this->Form->control('nom', ['label' => $label, 'class' => $champ]) ?>
    <?= $this->Form->control('description', ['type' => 'textarea', 'rows' => 3, 'label' => $label, 'class' => $champ]) ?>

    <?= $this->Form->control('client_id', [
        'type' => 'select',
        'options' => $clients,
        'empty' => '— Aucun compte associé —',
        'label' => ['text' => 'Client destinataire'] + $label,
        'class' => $champ,
    ]) ?>
    <p class="-mt-3 text-xs text-gris">
        Sans compte associé, la galerie reste accessible par son lien de partage,
        mais n'apparaîtra dans l'espace membre de personne.
    </p>

    <div>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'value' => '',
            'label' => ['text' => 'Mot de passe'] + $label,
            'class' => $champ,
        ]) ?>
        <p class="mt-1 text-xs text-gris">
            <?= $galerie->estProtege()
                ? 'Un mot de passe est déjà défini. Laissez vide pour le conserver.'
                : 'Laissez vide pour ne pas protéger cette galerie.' ?>
        </p>
    </div>

    <?= $this->Form->control('date_livraison', [
        'type' => 'date',
        'empty' => true,
        'label' => ['text' => 'Date de livraison'] + $label,
        'class' => $champ,
    ]) ?>
    <?= $this->Form->control('expires_at', [
        'type' => 'datetime-local',
        'empty' => true,
        'label' => ['text' => 'Expire le (facultatif)'] + $label,
        'class' => $champ,
    ]) ?>
    <?= $this->Form->control('quota_favoris', [
        'type' => 'number',
        'min' => 0,
        'label' => ['text' => 'Nombre de favoris autorisés (0 = illimité)'] + $label,
        'class' => $champ,
    ]) ?>

    <?= $this->Form->control('telechargement_actif', [
        'label' => ['text' => 'Autoriser le téléchargement', 'class' => 'text-sm text-gris'],
    ]) ?>
    <?= $this->Form->control('actif', ['label' => ['text' => 'En ligne', 'class' => 'text-sm text-gris']]) ?>

    <?= $this->Form->button('Enregistrer', [
        'class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
    ]) ?>
<?= $this->Form->end() ?>


<?php if (!$galerie->isNew()) : ?>
    <p class="mt-8">
        <a class="inline-block border border-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-corail hover:bg-corail hover:text-noir"
           href="<?= $this->Url->build([
               'prefix' => 'Admin',
               'controller' => 'SelectionPhotos',
               'action' => 'index',
               'galerie',
               $galerie->id,
           ]) ?>">
            Choisir les photos
        </a>
    </p>
<?php endif; ?>

<?php if (!$galerie->isNew()) : ?>
    <p class="mt-8 text-sm text-gris">
        Lien de partage :
        <code class="select-all break-all text-blanc-casse">
            <?= h($this->Url->build('/g/' . $galerie->share_token, ['fullBase' => true])) ?>
        </code>
    </p>

<?php endif; ?>
