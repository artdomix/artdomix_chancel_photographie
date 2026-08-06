<?php
/**
 * Formulaire générique d'une section du back-office.
 *
 * Les champs viennent de `CrudController::champs()`. Le type n'est presque
 * jamais déclaré : le FormHelper le déduit du schéma de la table, ce qui évite
 * de tenir à jour une seconde description des colonnes à côté des migrations.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $entite
 * @var array<string, array<string, mixed>> $champs
 * @var string $sectionTitre
 * @var string $sectionSingulier
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
$label = ['class' => 'mb-2 block text-sm text-gris'];
?>
<div class="mb-8">
    <a class="lien-souligne text-sm text-gris"
       href="<?= $this->Url->build(['action' => 'index']) ?>">← <?= h($sectionTitre) ?></a>
    <h1 class="mt-2 text-2xl">
        <?= $entite->isNew()
            ? h('Nouveau : ' . $sectionSingulier)
            : h('Modifier : ' . $sectionSingulier) ?>
    </h1>
</div>

<?= $this->Form->create($entite, ['class' => 'max-w-xl space-y-5']) ?>
    <?php foreach ($champs as $nom => $options) : ?>
        <?php
        // Une case à cocher ne prend ni la bordure ni la largeur d'un champ
        // texte : lui appliquer le style commun la rendrait démesurée. Le type a
        // été résolu par le contrôleur, qui a le schéma sous la main.
        $defauts = ($options['type'] ?? null) === 'checkbox'
            ? ['label' => ['class' => 'text-sm text-gris']]
            : ['label' => $label, 'class' => $champ];

        // Les options déclarées par le contrôleur priment sur les valeurs par
        // défaut, y compris pour le libellé.
        $options += $defauts;

        if (isset($options['label']) && is_string($options['label'])) {
            $options['label'] = ['text' => $options['label']] + $defauts['label'];
        }

        $aide = $options['aide'] ?? null;
        unset($options['aide']);
        ?>
        <div>
            <?= $this->Form->control($nom, $options) ?>
            <?php if ($aide !== null) : ?>
                <p class="mt-1 text-xs text-gris"><?= h($aide) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="flex items-center gap-6 pt-2">
        <?= $this->Form->button('Enregistrer', [
            'class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
        ]) ?>
        <a class="text-sm text-gris hover:text-corail"
           href="<?= $this->Url->build(['action' => 'index']) ?>">Annuler</a>
    </div>
<?= $this->Form->end() ?>

<?php if (!$entite->isNew()) : ?>
    <div class="mt-10 border-t border-white/10 pt-6">
        <?= $this->Form->postLink('Supprimer définitivement', ['action' => 'supprimer', $entite->id], [
            'class' => 'text-sm text-gris hover:text-red-400',
            'confirm' => 'Cet élément sera définitivement supprimé. Continuer ?',
        ]) ?>
    </div>
<?php endif; ?>
