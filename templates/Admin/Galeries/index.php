<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $galeries
 */

use Cake\I18n\DateTime;

$maintenant = new DateTime();
?>
<div class="mb-8 flex items-center justify-between">
    <h1 class="text-2xl">Galeries client</h1>
    <a class="bg-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre"
       href="<?= $this->Url->build(['action' => 'modifier']) ?>">Nouvelle</a>
</div>

<table class="w-full text-sm">
    <caption class="sr-only">Galeries livrées aux clients</caption>
    <thead class="border-b border-white/10 text-left text-gris">
        <tr>
            <th scope="col" class="py-2">Nom</th>
            <th scope="col" class="py-2">Client</th>
            <th scope="col" class="py-2">Livrée le</th>
            <th scope="col" class="py-2">Expire</th>
            <th scope="col" class="py-2">État</th>
            <th scope="col" class="py-2 text-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($galeries as $galerie) : ?>
            <?php $expiree = $galerie->expires_at !== null && $galerie->expires_at < $maintenant; ?>
            <tr class="border-b border-white/5">
                <td class="py-2">
                    <?= h($galerie->nom) ?>
                    <?php if ($galerie->estProtege()) : ?>
                        <span class="ml-1 text-xs text-corail" title="Protégée par mot de passe">🔒</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 text-gris"><?= h($galerie->client->email ?? '—') ?></td>
                <td class="py-2 text-gris"><?= h($galerie->date_livraison?->format('d/m/Y') ?? '—') ?></td>
                <td class="py-2 <?= $expiree ? 'text-red-400' : 'text-gris' ?>">
                    <?= h($galerie->expires_at?->format('d/m/Y') ?? '—') ?>
                </td>
                <td class="py-2 text-gris">
                    <?php if (!$galerie->actif) : ?>
                        Hors ligne
                    <?php elseif ($expiree) : ?>
                        Expirée
                    <?php else : ?>
                        En ligne<?= $galerie->telechargement_actif ? ', téléchargeable' : '' ?>
                    <?php endif; ?>
                </td>
                <td class="py-2 text-right">
                    <span class="flex justify-end gap-3">
                        <a class="lien-souligne" target="_blank" rel="noopener"
                           href="<?= $this->Url->build('/g/' . $galerie->share_token) ?>">Voir</a>
                        <a class="lien-souligne"
                           href="<?= $this->Url->build(['action' => 'favoris', $galerie->id]) ?>">Favoris</a>
                        <a class="lien-souligne"
                           href="<?= $this->Url->build(['action' => 'modifier', $galerie->id]) ?>">Modifier</a>
                        <?= $this->Form->postLink('Révoquer le lien', ['action' => 'revoquer', $galerie->id], [
                            'class' => 'text-gris hover:text-red-400',
                            'confirm' => 'Les liens déjà envoyés cesseront de fonctionner. Continuer ?',
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
