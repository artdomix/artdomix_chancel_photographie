<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Galerie $galerie
 * @var \App\Model\Entity\GalerieCommentaire $commentaire
 * @var list<int> $favoris
 * @var bool $peutTelecharger
 * @var bool $peutChoisir
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-4 py-3 focus:border-corail';
?>
<nav class="mb-8 text-sm text-gris" aria-label="Fil d'Ariane">
    <a class="lien-souligne"
       href="<?= $this->Url->build(['prefix' => 'Membre', 'controller' => 'Tableau', 'action' => 'index']) ?>">
        Mon espace
    </a>
    <span class="mx-2">/</span>
    <span aria-current="page" class="text-blanc-casse"><?= h($galerie->nom) ?></span>
</nav>

<h1 class="text-2xl md:text-4xl"><?= h($galerie->nom) ?></h1>
<?php if ($galerie->description) : ?>
    <p class="mt-3 max-w-2xl text-gris"><?= h($galerie->description) ?></p>
<?php endif; ?>

<div class="mt-6 flex flex-wrap items-center gap-6 text-sm text-gris">
    <span><?= h((string)count($galerie->photos)) ?> photos</span>
    <?php if ($galerie->quota_favoris > 0) : ?>
        <span role="status">
            <?= h((string)count($favoris)) ?> / <?= h((string)$galerie->quota_favoris) ?> retenues
        </span>
    <?php endif; ?>
    <?php if ($peutTelecharger) : ?>
        <?php
        // L'URL reste dans l'espace membre : le compte suffit à autoriser, il
        // n'y a pas de mot de passe de galerie à franchir ici.
        ?>
        <a class="lien-souligne text-corail"
           href="<?= $this->Url->build([
               'prefix' => 'Membre',
               'controller' => 'Galeries',
               'action' => 'telecharger',
               $galerie->id,
           ]) ?>">
            Télécharger la sélection (ZIP)
        </a>
    <?php endif; ?>
</div>

<?php if ($galerie->expires_at !== null) : ?>
    <p class="mt-4 text-sm text-gris">
        Cette galerie reste accessible jusqu'au <?= h($galerie->expires_at->format('d/m/Y')) ?>.
    </p>
<?php endif; ?>

<div data-anim="grille" data-galerie
     class="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
    <?php foreach ($galerie->photos as $photo) : ?>
        <div data-anim-item>
            <?= $this->Photo->vignette($photo, [
                'sizes' => '(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw',
            ]) ?>
            <?php if ($peutChoisir) : ?>
                <?= $this->element('bouton_favori', [
                    'galerie' => $galerie,
                    'photo' => $photo,
                    'actif' => in_array($photo->id, $favoris, true),
                    'url' => [
                        'prefix' => 'Membre',
                        'controller' => 'Galeries',
                        'action' => 'favori',
                        $galerie->id,
                        $photo->id,
                    ],
                ]) ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<section class="mt-16 max-w-2xl border-t border-white/10 pt-10">
    <h2 class="mb-6 text-xl">Échanges avec le photographe</h2>

    <?php if (count($galerie->galerie_commentaires) > 0) : ?>
        <ul class="mb-10 space-y-6">
            <?php foreach ($galerie->galerie_commentaires as $retour) : ?>
                <li class="border-l-2 border-white/10 pl-4">
                    <p class="text-sm text-gris">
                        <?= h($retour->user->nom_complet ?? $retour->auteur ?? 'Anonyme') ?>
                        — <?= h($retour->created?->format('d/m/Y à H:i')) ?>
                    </p>
                    <p class="mt-2"><?= nl2br(h($retour->contenu)) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="mb-10 text-sm text-gris">
            Aucun échange pour l'instant. Utilisez ce formulaire pour signaler une
            retouche ou poser une question sur la livraison.
        </p>
    <?php endif; ?>

    <?= $this->Form->create($commentaire, [
        'url' => ['action' => 'commenter', $galerie->id],
        'class' => 'space-y-4',
    ]) ?>
        <?= $this->Form->control('contenu', [
            'type' => 'textarea',
            'rows' => 4,
            'label' => ['text' => 'Votre message', 'class' => 'mb-2 block text-sm text-gris'],
            'required' => true,
            'class' => $champ,
        ]) ?>
        <?= $this->Form->button('Envoyer', [
            'class' => 'border border-corail px-6 py-3 font-display text-sm uppercase tracking-titre text-corail hover:bg-corail hover:text-noir',
        ]) ?>
    <?= $this->Form->end() ?>
</section>
