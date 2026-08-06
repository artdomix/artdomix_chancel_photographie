<?php
/**
 * Moodboard vu depuis le compte du destinataire.
 *
 * La mise en page reprend celle du partage public, mais sans le mot de passe :
 * le compte a déjà tranché. Le thème d'animation est le même — c'est le même
 * moodboard, il doit se présenter de la même façon.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Moodboard $moodboard
 * @var \App\Model\Entity\MoodboardCommentaire $commentaire
 * @var bool $peutCommenter
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-4 py-3 focus:border-corail';
$theme = $moodboard->theme->value;

$this->append('script', $this->Assets->moodboard());
?>
<nav class="mb-8 text-sm text-gris" aria-label="Fil d'Ariane">
    <a class="lien-souligne"
       href="<?= $this->Url->build(['prefix' => 'Membre', 'controller' => 'Tableau', 'action' => 'index']) ?>">
        Mon espace
    </a>
    <span class="mx-2">/</span>
    <span aria-current="page" class="text-blanc-casse"><?= h($moodboard->titre) ?></span>
</nav>

<h1 class="text-2xl md:text-4xl"><?= h($moodboard->titre) ?></h1>
<?php if ($moodboard->description) : ?>
    <p class="mt-3 max-w-2xl text-gris"><?= h($moodboard->description) ?></p>
<?php endif; ?>

<?php if (count($moodboard->photos) === 0) : ?>
    <p class="py-20 text-center text-gris">Cette sélection est encore vide.</p>
<?php else : ?>
    <div data-moodboard data-theme="<?= h($theme) ?>" data-galerie
         class="mt-10 moodboard moodboard--<?= h($theme) ?>">
        <?php if ($theme === 'mur-parallaxe') : ?>
            <?php
            // Découpage en colonnes fait côté serveur : la page reste correcte
            // même si le JS ne prend pas la main.
            $colonnes = [[], [], []];

            foreach ($moodboard->photos as $i => $photo) {
                $colonnes[$i % 3][] = $photo;
            }
            ?>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                <?php foreach ($colonnes as $colonne) : ?>
                    <div data-moodboard-colonne class="space-y-4">
                        <?php foreach ($colonne as $photo) : ?>
                            <div data-moodboard-item>
                                <?= $this->Photo->vignette($photo, ['sizes' => '(min-width: 768px) 33vw, 50vw']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($theme === 'carrousel-pinne') : ?>
            <div class="overflow-hidden">
                <div data-moodboard-piste class="flex gap-6 overflow-x-auto pb-6 md:overflow-x-visible">
                    <?php foreach ($moodboard->photos as $photo) : ?>
                        <div data-moodboard-item class="w-[70vw] shrink-0 md:w-[38vw]">
                            <?= $this->Photo->vignette($photo, ['sizes' => '(min-width: 768px) 38vw, 70vw']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else : ?>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5">
                <?php foreach ($moodboard->photos as $photo) : ?>
                    <?php $miseEnAvant = (bool)($photo->_joinData->mise_en_avant ?? false); ?>
                    <div data-moodboard-item class="<?= $miseEnAvant ? 'col-span-2 row-span-2' : '' ?>">
                        <?= $this->Photo->vignette($photo, [
                            'sizes' => $miseEnAvant
                                ? '(min-width: 1024px) 50vw, 100vw'
                                : '(min-width: 1024px) 33vw, 50vw',
                        ]) ?>
                        <?php if (!empty($photo->_joinData->note)) : ?>
                            <p class="mt-2 text-xs text-gris"><?= h($photo->_joinData->note) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($moodboard->commentaires_actifs) : ?>
    <section class="mt-16 max-w-2xl border-t border-white/10 pt-10">
        <h2 class="mb-6 text-xl">Vos retours</h2>

        <?php if (count($moodboard->moodboard_commentaires) > 0) : ?>
            <ul class="mb-10 space-y-6">
                <?php foreach ($moodboard->moodboard_commentaires as $retour) : ?>
                    <li class="border-l-2 border-white/10 pl-4">
                        <p class="text-sm text-gris">
                            <?= h($retour->auteur ?: 'Anonyme') ?>
                            — <?= h($retour->created?->format('d/m/Y')) ?>
                        </p>
                        <p class="mt-2"><?= nl2br(h($retour->contenu)) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($peutCommenter) : ?>
            <?= $this->Form->create($commentaire, [
                'url' => ['action' => 'commenter', $moodboard->id],
                'class' => 'space-y-4',
            ]) ?>
                <?= $this->Form->control('auteur', [
                    'label' => ['text' => 'Votre nom', 'class' => 'mb-2 block text-sm text-gris'],
                    'required' => true,
                    'class' => $champ,
                ]) ?>
                <?= $this->Form->control('contenu', [
                    'type' => 'textarea',
                    'rows' => 4,
                    'label' => ['text' => 'Votre retour', 'class' => 'mb-2 block text-sm text-gris'],
                    'required' => true,
                    'class' => $champ,
                ]) ?>
                <?= $this->Form->button('Envoyer', [
                    'class' => 'border border-corail px-6 py-3 font-display text-sm uppercase tracking-titre text-corail hover:bg-corail hover:text-noir',
                ]) ?>
            <?= $this->Form->end() ?>
        <?php endif; ?>
    </section>
<?php endif; ?>
