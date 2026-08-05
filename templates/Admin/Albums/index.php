<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $albums
 */

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
        $html .= '<span>' . h($album->nom);
        $html .= $album->visibilite === 'prive'
            ? ' <span class="ml-2 text-xs uppercase tracking-titre text-gris">privé</span>'
            : '';
        $html .= '</span><span class="flex gap-3 text-sm">';
        $html .= $this->Form->postLink('↑', ['action' => 'deplacer', $album->id, 'haut'], [
            'class' => 'text-gris hover:text-corail', 'escapeTitle' => false, 'title' => 'Monter',
        ]);
        $html .= $this->Form->postLink('↓', ['action' => 'deplacer', $album->id, 'bas'], [
            'class' => 'text-gris hover:text-corail', 'escapeTitle' => false, 'title' => 'Descendre',
        ]);
        $html .= $this->Html->link('Modifier', ['action' => 'modifier', $album->id], ['class' => 'lien-souligne']);
        $html .= '</span></div>';

        if (!empty($album->children)) {
            $html .= '<ul>' . $brancher($album->children, $profondeur + 1) . '</ul>';
        }

        $html .= '</li>';
    }

    return $html;
};
?>
<h1 class="mb-8 text-2xl">Albums</h1>

<ul class="max-w-3xl">
    <?= $brancher($albums) ?>
</ul>
