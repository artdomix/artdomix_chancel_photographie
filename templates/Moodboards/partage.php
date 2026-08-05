<?php
/**
 * Rendu public d'un moodboard.
 *
 * La mise en page dépend du thème choisi dans l'admin : chaque thème attend une
 * structure HTML précise (colonnes pour le parallaxe, piste unique pour le
 * carrousel), et le module JS correspondant se charge d'après `data-theme`.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Moodboard $moodboard
 * @var \App\Model\Entity\MoodboardCommentaire $commentaire
 */

$photos = $moodboard->photos;
$champ = 'w-full border border-white/20 bg-noir-clair px-4 py-3 focus:border-corail';
$theme = $moodboard->theme->value;

// Les thèmes d'animation ne sont chargés que sur cette page.
$this->append('script', $this->Assets->moodboard());
?>
<section class="mx-auto max-w-7xl px-6 py-12">
    <h1 class="text-3xl md:text-5xl"><?= h($moodboard->titre) ?></h1>
    <?php if ($moodboard->description) : ?>
        <p class="mt-4 max-w-2xl text-gris"><?= h($moodboard->description) ?></p>
    <?php endif; ?>
</section>

<?php if (count($photos) === 0) : ?>
    <p class="px-6 py-20 text-center text-gris">Cette sélection est encore vide.</p>
<?php else : ?>
    <div data-moodboard data-theme="<?= h($theme) ?>"
         data-galerie class="moodboard moodboard--<?= h($theme) ?>">

        <?php if ($theme === 'mur-parallaxe') : ?>
            <?php
            // Réparti en trois colonnes, que le thème anime à des vitesses
            // différentes. Le découpage est fait ici, côté serveur, pour que la
            // page reste correcte même sans JavaScript.
            $colonnes = [[], [], []];

            foreach ($photos as $i => $photo) {
                $colonnes[$i % 3][] = $photo;
            }
            ?>
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-6 pb-24 md:grid-cols-3">
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
                <?php
                // `overflow-x-auto` en repli : si le JS ne prend pas la main, la
                // piste reste défilable au doigt ou à la molette.
                ?>
                <div data-moodboard-piste
                     class="flex gap-6 overflow-x-auto px-6 pb-24 md:overflow-x-visible">
                    <?php foreach ($photos as $photo) : ?>
                        <div data-moodboard-item class="w-[70vw] shrink-0 md:w-[38vw]">
                            <?= $this->Photo->vignette($photo, ['sizes' => '(min-width: 768px) 38vw, 70vw']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else : ?>
            <?php // mosaique-flip et grille-cinetique partagent la même grille. ?>
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-3 px-6 pb-24 md:grid-cols-3 md:gap-5 lg:grid-cols-4">
                <?php foreach ($photos as $photo) : ?>
                    <?php
                    // Une photo mise en avant occupe deux cases : c'est le seul
                    // moyen pour le photographe de hiérarchiser sa sélection.
                    $miseEnAvant = (bool)($photo->_joinData->mise_en_avant ?? false);
                    ?>
                    <div data-moodboard-item
                         class="<?= $miseEnAvant ? 'col-span-2 row-span-2' : '' ?>">
                        <?= $this->Photo->vignette($photo, [
                            'sizes' => $miseEnAvant
                                ? '(min-width: 1024px) 50vw, 100vw'
                                : '(min-width: 1024px) 25vw, 50vw',
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
    <section class="mx-auto max-w-3xl border-t border-white/10 px-6 py-12">
        <h2 class="mb-8 text-xl">Vos retours</h2>

        <?php if (count($moodboard->moodboard_commentaires) > 0) : ?>
            <ul class="mb-10 space-y-6">
                <?php foreach ($moodboard->moodboard_commentaires as $c) : ?>
                    <li class="border-l-2 border-white/10 pl-4">
                        <p class="text-sm text-gris">
                            <?= h($c->auteur ?: 'Anonyme') ?> — <?= h($c->created?->format('d/m/Y')) ?>
                        </p>
                        <p class="mt-2"><?= nl2br(h($c->contenu)) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?= $this->Form->create($commentaire, [
            'url' => ['action' => 'commenter', $moodboard->share_token],
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
    </section>
<?php endif; ?>
