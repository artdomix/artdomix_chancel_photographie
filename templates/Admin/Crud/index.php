<?php
/**
 * Liste générique d'une section du back-office.
 *
 * Rendu par `Admin\CrudController::index()` pour toutes les sections régulières.
 * Les sections qui ont besoin d'autre chose (photos, moodboards, galeries,
 * messages) gardent leur propre gabarit.
 *
 * @var \App\View\AppView $this
 * @var iterable $entites
 * @var array<string, string> $colonnes
 * @var string|null $colonneBascule
 * @var array{0: string, 1: string} $libellesBascule
 * @var bool $creationPossible
 * @var string $sectionTitre
 * @var string $sectionSingulier
 */

$lignes = [];

foreach ($entites as $entite) {
    $lignes[] = $entite;
}
?>
<div class="mb-8 flex items-center justify-between">
    <h1 class="text-2xl"><?= h($sectionTitre) ?></h1>
    <?php if ($creationPossible) : ?>
        <a class="bg-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre"
           href="<?= $this->Url->build(['action' => 'modifier']) ?>">
            Ajouter
        </a>
    <?php endif; ?>
</div>

<?php if ($lignes === []) : ?>
    <p class="py-16 text-center text-gris">
        <?= $creationPossible
            ? "Rien pour l'instant. Utilisez « Ajouter » pour créer un premier élément."
            : "Rien pour l'instant." ?>
    </p>
<?php else : ?>
    <table class="w-full text-sm">
        <caption class="sr-only"><?= h($sectionTitre) ?></caption>
        <thead class="border-b border-white/10 text-left text-gris">
            <tr>
                <?php foreach ($colonnes as $libelle) : ?>
                    <th scope="col" class="py-2 pr-4"><?= h($libelle) ?></th>
                <?php endforeach; ?>
                <th scope="col" class="py-2 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $entite) : ?>
                <tr class="border-b border-white/5">
                    <?php foreach (array_keys($colonnes) as $champ) : ?>
                        <td class="py-2 pr-4"><?= $this->Admin->valeur($entite, $champ) ?></td>
                    <?php endforeach; ?>
                    <td class="py-2 text-right">
                        <span class="flex justify-end gap-3">
                            <?php if ($colonneBascule !== null) : ?>
                                <?= $this->Form->postLink(
                                    $entite->get($colonneBascule) ? $libellesBascule[0] : $libellesBascule[1],
                                    ['action' => 'basculer', $entite->id],
                                    ['class' => 'text-gris hover:text-corail'],
                                ) ?>
                            <?php endif; ?>
                            <a class="lien-souligne"
                               href="<?= $this->Url->build(['action' => 'modifier', $entite->id]) ?>">Modifier</a>
                            <?= $this->Form->postLink('Supprimer', ['action' => 'supprimer', $entite->id], [
                                'class' => 'text-gris hover:text-red-400',
                                'confirm' => 'Cet élément sera définitivement supprimé. Continuer ?',
                            ]) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($this->Paginator->hasNext() || $this->Paginator->hasPrev()) : ?>
        <nav class="mt-8 flex gap-3 text-sm" aria-label="Pagination">
            <?= $this->Paginator->prev('← Précédent') ?>
            <?= $this->Paginator->next('Suivant →') ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
