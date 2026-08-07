<?php
/**
 * Photothèque : la réserve dans laquelle on pioche.
 *
 * Rendue seule lors d'une recherche htmx, pour que la sélection courante affichée
 * au-dessus ne soit pas rechargée à chaque frappe.
 *
 * @var \App\View\AppView $this
 * @var \App\Service\Association\Liaison $liaison
 * @var \Cake\Datasource\EntityInterface $cible
 * @var iterable $phototheque
 * @var list<int> $liees
 * @var string $recherche
 * @var string $filtre
 */
?>
<div id="phototheque">
    <?php if (count($phototheque) === 0) : ?>
        <p class="py-12 text-center text-gris">
            <?= match (true) {
                $recherche !== '' => 'Aucune photo ne correspond à cette recherche.',
                $filtre === 'libres' => 'Toute la photothèque est déjà rattachée.',
                $filtre === 'liees' => 'Aucune photo rattachée pour l\'instant.',
                default => 'Aucune photo importée. Commencez par en importer depuis la section Photos.',
            } ?>
        </p>
    <?php else : ?>
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <?php foreach ($phototheque as $photo) : ?>
                <?= $this->element('admin/vignette_selectionnable', [
                    'liaison' => $liaison,
                    'cible' => $cible,
                    'photo' => $photo,
                    'liee' => in_array($photo->id, $liees, true),
                ]) ?>
            <?php endforeach; ?>
        </ul>

        <?php if ($this->Paginator->hasNext() || $this->Paginator->hasPrev()) : ?>
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Pagination de la photothèque">
                <?= $this->Paginator->prev('← Précédent') ?>
                <span class="text-gris"><?= $this->Paginator->counter('Page {{page}} sur {{pages}}') ?></span>
                <?= $this->Paginator->next('Suivant →') ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
