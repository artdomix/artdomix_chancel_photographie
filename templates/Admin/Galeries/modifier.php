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
    <p class="mt-8 text-sm text-gris">
        Lien de partage :
        <code class="select-all break-all text-blanc-casse">
            <?= h($this->Url->build('/g/' . $galerie->share_token, ['fullBase' => true])) ?>
        </code>
    </p>

    <?php if (count($galerie->photos) > 0) : ?>
        <?php
        // Le glisser-déposer est piloté par `chancel-admin.js`, qui poste le
        // nouvel ordre en JSON. Le jeton CSRF est passé en attribut : le script
        // le renvoie dans l'en-tête `X-CSRF-Token`.
        ?>
        <h2 class="mb-2 mt-12 text-lg">Ordre des photos</h2>
        <p class="mb-4 text-sm text-gris">
            Glissez les vignettes pour les réordonner, ou utilisez les flèches
            gauche et droite après avoir sélectionné une vignette au clavier.
        </p>
        <p data-tri-etat class="mb-4 text-sm text-corail" role="status" aria-live="polite"></p>

        <div data-tri
             data-tri-url="<?= $this->Url->build(['action' => 'ordonner', $galerie->id]) ?>"
             data-csrf="<?= h($this->getRequest()->getAttribute('csrfToken')) ?>"
             class="grid grid-cols-3 gap-3 md:grid-cols-6">
            <?php foreach ($galerie->photos as $photo) : ?>
                <div data-tri-item="<?= h((string)$photo->id) ?>" tabindex="0"
                     class="cursor-grab focus:outline focus:outline-2 focus:outline-corail">
                    <img src="<?= h($this->Photo->url($photo, 'thumb', 'jpeg')) ?>"
                         alt="<?= h((string)($photo->titre ?? '')) ?>" width="160" height="107"
                         loading="lazy" class="w-full object-cover">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
