<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $moodboards
 * @var array<int, \App\Model\Entity\Photo> $apercus
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
            <th scope="col" class="py-2"><span class="sr-only">Aperçu</span></th>
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
                <td class="py-2 pr-3">
                    <?php $apercu = $apercus[$moodboard->id] ?? null; ?>
                    <?php if ($apercu !== null) : ?>
                        <img src="<?= h($this->Photo->url($apercu, 'thumb', 'jpeg')) ?>" alt=""
                             width="64" height="43" loading="lazy" class="h-9 w-14 object-cover">
                    <?php else : ?>
                        <span class="flex h-9 w-14 items-center justify-center border border-dashed
                                     border-white/20 text-[10px] leading-tight text-gris"
                              title="Sélection vide">sans<br>photo</span>
                    <?php endif; ?>
                </td>
                <td class="py-2"><?= h($moodboard->titre) ?></td>
                <td class="py-2 text-gris"><?= h($moodboard->theme->label()) ?></td>
                <td class="py-2">
                    <?= h($moodboard->visibilite->label()) ?>
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
                        <?= $this->Form->postLink('Supprimer', ['action' => 'supprimer', $moodboard->id], [
                            'class' => 'text-gris hover:text-red-400',
                            'confirm' => 'Le moodboard et ses retours seront supprimés. Les photos, elles, sont conservées. Continuer ?',
                        ]) ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
