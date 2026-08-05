<?php
/**
 * Grille de photos, réutilisée par les albums, les tags et la recherche.
 *
 * Sert aussi de réponse aux requêtes htmx de pagination : le fragment est
 * autonome, il ne dépend d'aucune variable du layout.
 *
 * @var \App\View\AppView $this
 * @var iterable $photos
 * @var bool|null $paginer
 */

// L'accueil affiche une sélection non paginée : appeler le Paginator sur une
// requête qui n'a pas été paginée lève une exception. La pagination est donc
// explicitement demandée par les pages qui en ont une.
$paginer = $paginer ?? false;
?>
<?php if (count($photos) === 0) : ?>
    <p class="py-16 text-center text-gris">Aucune photo pour l'instant.</p>
<?php else : ?>
    <div data-anim="grille" data-galerie
         class="grid grid-cols-2 gap-2 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
        <?php foreach ($photos as $photo) : ?>
            <?= $this->Photo->vignette($photo, ['sizes' => '(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw']) ?>
        <?php endforeach; ?>
    </div>

    <?php if ($paginer && $this->Paginator->hasNext()) : ?>
        <?php
        // Chargement de la page suivante au scroll. `hx-swap="afterend"` ajoute
        // la grille suivante après celle-ci, plutôt que de remplacer la page.
        ?>
        <div class="py-10 text-center"
             hx-get="<?= h($this->Paginator->generateUrl(['page' => $this->Paginator->current() + 1])) ?>"
             hx-trigger="revealed"
             hx-swap="outerHTML">
            <span class="text-sm text-gris">Chargement…</span>
            <noscript>
                <?= $this->Paginator->next('Page suivante', ['class' => 'lien-souligne']) ?>
            </noscript>
        </div>
    <?php endif; ?>
<?php endif; ?>
