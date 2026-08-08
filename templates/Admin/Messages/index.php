<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $messages
 * @var string $filtre
 * @var int $nbNonLus
 */

$onglets = [
    'tous' => 'Tous',
    'non-lus' => 'Non lus' . ($nbNonLus > 0 ? ' (' . $nbNonLus . ')' : ''),
    'a-traiter' => 'À traiter',
];
?>
<h1 class="mb-6 text-2xl">Messages</h1>

<nav class="mb-8 flex gap-6 text-sm" aria-label="Filtrer les messages">
    <?php foreach ($onglets as $cle => $libelle) : ?>
        <?php
        // L'état actif est calculé avant la balise plutôt que dans deux
        // ternaires successifs : phpcbf, qui analyse les blocs PHP d'un
        // gabarit comme un flux continu, réécrivait le second de travers.
        $actif = $filtre === $cle;
        ?>
        <a class="<?= $actif ? 'text-corail' : 'lien-souligne text-gris' ?>"
           <?= $actif ? 'aria-current="page"' : '' ?>
           href="<?= $this->Url->build(['action' => 'index', '?' => ['filtre' => $cle]]) ?>">
            <?= h($libelle) ?>
        </a>
    <?php endforeach; ?>
</nav>

<table class="w-full text-sm">
    <caption class="sr-only">Messages reçus par le formulaire de contact</caption>
    <thead class="border-b border-white/10 text-left text-gris">
        <tr>
            <th scope="col" class="py-2">Reçu le</th>
            <th scope="col" class="py-2">Expéditeur</th>
            <th scope="col" class="py-2">Sujet</th>
            <th scope="col" class="py-2">État</th>
            <th scope="col" class="py-2 text-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($messages as $message) : ?>
            <tr class="border-b border-white/5 <?= $message->lu ? '' : 'font-semibold' ?>">
                <td class="py-2 text-gris"><?= h($message->created?->format('d/m/Y H:i')) ?></td>
                <td class="py-2">
                    <?= h($message->nom) ?>
                    <span class="block text-xs text-gris"><?= h($message->email) ?></span>
                </td>
                <td class="py-2"><?= h($message->sujet ?: '—') ?></td>
                <td class="py-2 text-gris">
                    <?php if ($message->traite) : ?>
                        Traité
                    <?php elseif ($message->lu) : ?>
                        Lu
                    <?php else : ?>
                        <span class="text-corail">Nouveau</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 text-right">
                    <span class="flex justify-end gap-3">
                        <a class="lien-souligne"
                           href="<?= $this->Url->build(['action' => 'voir', $message->id]) ?>">Ouvrir</a>
                        <?= $this->Form->postLink(
                            $message->traite ? 'Rouvrir' : 'Marquer traité',
                            ['action' => 'basculerTraite', $message->id],
                            ['class' => 'text-gris hover:text-corail'],
                        ) ?>
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
