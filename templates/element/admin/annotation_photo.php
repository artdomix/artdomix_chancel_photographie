<?php
/**
 * Note et mise en avant d'une photo dans un moodboard.
 *
 * Ces deux colonnes de la jonction sont lues par le gabarit public — une photo
 * mise en avant occupe deux cases dans la mosaïque — mais rien ne permettait de
 * les saisir.
 *
 * @var \App\View\AppView $this
 * @var \App\Service\Association\Liaison $liaison
 * @var \Cake\Datasource\EntityInterface $cible
 * @var \App\Model\Entity\Photo $photo
 */

$jointure = $photo->_joinData ?? null;
?>
<?= $this->Form->create(null, [
    'url' => [
        'prefix' => 'Admin',
        'controller' => 'SelectionPhotos',
        'action' => 'annoter',
        $liaison->type,
        $cible->id,
        $photo->id,
    ],
    'class' => 'space-y-1 border-t border-white/10 pt-2',
]) ?>
    <label class="block text-gris" for="note-<?= h((string)$photo->id) ?>">Note</label>
    <input id="note-<?= h((string)$photo->id) ?>" type="text" name="note"
           value="<?= h((string)($jointure->note ?? '')) ?>"
           placeholder="Affichée sous la photo"
           class="w-full border border-white/20 bg-noir-clair px-2 py-1 focus:border-corail">

    <label class="flex items-center gap-2 text-gris">
        <?= $this->Form->checkbox('mise_en_avant', [
            'checked' => (bool)($jointure->mise_en_avant ?? false),
            'hiddenField' => true,
        ]) ?>
        Mise en avant
    </label>

    <?= $this->Form->button('Enregistrer', [
        'class' => 'w-full border border-white/20 px-2 py-1 text-gris hover:border-corail hover:text-corail',
    ]) ?>
<?= $this->Form->end() ?>
