<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $moodboards
 */
?>
<div class="mb-8 flex items-center justify-between">
    <h1 class="text-2xl">Moodboards</h1>
    <a class="bg-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre"
       href="<?= $this->Url->build(['action' => 'modifier']) ?>">Nouveau</a>
</div>

<table class="w-full text-sm">
    <caption class="sr-only">Moodboards existants</caption>
    <thead class="border-b border-white/10 text-left text-gris">
        <tr>
            <th scope="col" class="py-2">Titre</th>
            <th scope="col" class="py-2">Thème</th>
            <th scope="col" class="py-2">Visibilité</th>
            <th scope="col" class="py-2">Destinataire</th>
            <th scope="col" class="py-2">Vues</th>
            <th scope="col" class="py-2 text-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($moodboards as $moodboard) : ?>
            <tr class="border-b border-white/5">
                <td class="py-2"><?= h($moodboard->titre) ?></td>
                <td class="py-2 text-gris"><?= h($moodboard->theme) ?></td>
                <td class="py-2">
                    <?= h($moodboard->visibilite) ?>
                    <?php if ($moodboard->estProtege()) : ?>
                        <span class="ml-1 text-xs text-corail" title="Protégé par mot de passe">🔒</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 text-gris"><?= h($moodboard->destinataire->email ?? '—') ?></td>
                <td class="py-2 text-gris"><?= h((string)$moodboard->vues) ?></td>
                <td class="py-2 text-right">
                    <span class="flex justify-end gap-3">
                        <a class="lien-souligne" target="_blank" rel="noopener"
                           href="<?= $this->Url->build('/m/' . $moodboard->share_token) ?>">Voir</a>
                        <a class="lien-souligne"
                           href="<?= $this->Url->build(['action' => 'modifier', $moodboard->id]) ?>">Modifier</a>
                        <?= $this->Form->postLink('Révoquer le lien', ['action' => 'revoquer', $moodboard->id], [
                            'class' => 'text-gris hover:text-red-400',
                            'confirm' => 'Les liens déjà envoyés cesseront de fonctionner. Continuer ?',
                        ]) ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
