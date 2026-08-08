<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $albums
 */

use App\Model\Enum\VisibiliteAlbum;

/**
 * Affiche récursivement l'arbre, en s'appuyant sur le résultat de find('threaded').
 *
 * `$this` n'est pas passé en `use` : dans un template, une closure est déjà liée
 * à la vue et le passer explicitement est une erreur de compilation.
 */
$brancher = function (iterable $noeuds, int $profondeur = 0) use (&$brancher): string {
    $html = '';

    foreach ($noeuds as $album) {
        $html .= '<li class="border-b border-white/5 py-2">';
        $html .= '<div class="flex items-center justify-between" style="padding-left:' . ($profondeur * 24) . 'px">';
        $html .= '<span class="flex items-center gap-3">';

        if ($album->cover_photo !== null) {
            $html .= sprintf(
                '<img src="%s" alt="" width="64" height="43" loading="lazy" class="h-9 w-14 object-cover">',
                h($this->Photo->url($album->cover_photo, 'thumb', 'jpeg')),
            );
        } else {
            $html .= '<span class="flex h-9 w-14 items-center justify-center border border-dashed '
                . 'border-white/20 text-[10px] leading-tight text-gris" '
                . 'title="Cette série s\'affiche sans image sur le site">sans<br>image</span>';
        }

        $html .= h($album->nom);
        $html .= $album->visibilite === VisibiliteAlbum::Prive
            ? ' <span class="ml-2 text-xs uppercase tracking-titre text-gris">privé</span>'
            : '';
        $html .= '</span><span class="flex items-center gap-3 text-sm">';
        $html .= $this->Form->postLink('↑', ['action' => 'deplacer', $album->id, 'haut'], [
            'class' => 'text-gris hover:text-corail', 'escapeTitle' => false, 'title' => 'Monter',
        ]);
        $html .= $this->Form->postLink('↓', ['action' => 'deplacer', $album->id, 'bas'], [
            'class' => 'text-gris hover:text-corail', 'escapeTitle' => false, 'title' => 'Descendre',
        ]);
        $html .= $this->Html->link('Modifier', ['action' => 'modifier', $album->id], ['class' => 'lien-souligne']);
        $html .= $this->Form->postLink('Supprimer', ['action' => 'supprimer', $album->id], [
            'class' => 'text-gris hover:text-red-400',
            'confirm' => "L'album et ses sous-albums seront supprimés. Les photos, elles, sont conservées. Continuer ?",
        ]);
        $html .= '</span></div>';

        if (!empty($album->children)) {
            $html .= '<ul>' . $brancher($album->children, $profondeur + 1) . '</ul>';
        }

        $html .= '</li>';
    }

    return $html;
};
?>
<div class="mb-8 flex items-center justify-between">
    <h1 class="text-2xl">Albums</h1>
    <a class="bg-corail px-5 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre"
       href="<?= $this->Url->build(['action' => 'modifier']) ?>">Nouvel album</a>
</div>

<?php if (count($albums) === 0) : ?>
    <p class="py-16 text-center text-gris">
        Aucun album. Créez-en un pour commencer à organiser le portfolio.
    </p>
<?php else : ?>
    <ul class="max-w-3xl">
        <?= $brancher($albums) ?>
    </ul>
<?php endif; ?>
