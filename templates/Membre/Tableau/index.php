<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $galeries
 * @var iterable $moodboards
 */
?>
<h1 class="mb-8 text-2xl">Mon espace</h1>

<section class="mb-12">
    <h2 class="mb-4 text-lg">Mes galeries</h2>
    <?php if (count($galeries) === 0) : ?>
        <p class="text-sm text-gris">Aucune galerie ne vous a encore été livrée.</p>
    <?php else : ?>
        <ul class="grid gap-4 md:grid-cols-2">
            <?php foreach ($galeries as $galerie) : ?>
                <li class="border border-white/10 bg-noir-carte p-5">
                    <a class="lien-souligne font-display uppercase tracking-titre"
                       href="<?= $this->Url->build(['prefix' => 'Membre', 'controller' => 'Galeries', 'action' => 'voir', $galerie->id]) ?>">
                        <?= h($galerie->nom) ?>
                    </a>
                    <p class="mt-2 text-sm text-gris">
                        Livrée le <?= h($galerie->date_livraison?->format('d/m/Y') ?? '—') ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section>
    <h2 class="mb-4 text-lg">Mes moodboards</h2>
    <?php if (count($moodboards) === 0) : ?>
        <p class="text-sm text-gris">Aucun moodboard partagé pour l'instant.</p>
    <?php else : ?>
        <ul class="grid gap-4 md:grid-cols-2">
            <?php foreach ($moodboards as $moodboard) : ?>
                <li class="border border-white/10 bg-noir-carte p-5">
                    <a class="lien-souligne font-display uppercase tracking-titre"
                       href="<?= $this->Url->build('/m/' . $moodboard->share_token) ?>">
                        <?= h($moodboard->titre) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
