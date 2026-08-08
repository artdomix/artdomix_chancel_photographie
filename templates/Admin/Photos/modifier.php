<?php
/**
 * Fiche d'une photo.
 *
 * Les photos ne passent pas par le gabarit générique du back-office : elles ont
 * des rattachements multiples (albums, tags), des métadonnées EXIF en lecture
 * seule et deux opérations propres au pipeline d'images — régénérer les dérivés
 * et supprimer les fichiers en même temps que la ligne.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Photo $photo
 * @var array<int, string> $tags
 * @var array<int, string> $albums
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-3 py-2 focus:border-corail';
$label = ['class' => 'mb-2 block text-sm text-gris'];

// Métadonnées affichées telles quelles : elles viennent du fichier, les
// modifier ici ne changerait pas l'original et induirait en erreur.
$exif = $photo->exif;
$metadonnees = $exif === null ? [] : array_filter([
    'Boîtier' => $exif->apn,
    'Objectif' => $exif->objectif,
    'Ouverture' => $exif->ouverture,
    'Vitesse' => $exif->exposition,
    'ISO' => $exif->iso === null ? null : (string)$exif->iso,
    'Focale' => $exif->focale,
    'Prise le' => $exif->date_capture?->format('d/m/Y à H:i'),
    'GPS' => $exif->gps_lat === null
        ? null
        : sprintf('%s, %s', $exif->gps_lat, $exif->gps_lng),
]);
?>
<div class="mb-8">
    <a class="lien-souligne text-sm text-gris"
       href="<?= $this->Url->build(['action' => 'index']) ?>">← Photos</a>
    <h1 class="mt-2 text-2xl"><?= h($photo->titre ?? $photo->fichier) ?></h1>
</div>

<div class="grid gap-10 lg:grid-cols-[20rem_1fr]">
    <div>
        <img src="<?= h($this->Photo->url($photo, 'grid', 'jpeg')) ?>"
             alt="<?= h((string)($photo->alt ?? '')) ?>"
             width="640" height="427" class="w-full object-cover">

        <p class="mt-3 text-xs text-gris">
            <?= h($photo->fichier) ?><br>
            <?= h((string)$photo->largeur) ?> × <?= h((string)$photo->hauteur) ?> px
            <?php if ($photo->poids_octets !== null) : ?>
                — <?= h((string)round($photo->poids_octets / 1024)) ?> Ko
            <?php endif; ?>
        </p>

        <?php if (!$photo->has_avif) : ?>
            <?php
            // L'AVIF est conditionnel : le GD de l'hébergeur ne le fournit pas
            // toujours. Le signaler évite de chercher un fichier absent.
            ?>
            <p class="mt-2 text-xs text-gris">Pas de déclinaison AVIF pour cette photo.</p>
        <?php endif; ?>

        <?php if ($metadonnees !== []) : ?>
            <h2 class="mb-2 mt-8 text-sm uppercase tracking-titre text-gris">Métadonnées</h2>
            <dl class="grid grid-cols-[6rem_1fr] gap-y-1 text-xs">
                <?php foreach ($metadonnees as $cle => $valeur) : ?>
                    <dt class="text-gris"><?= h($cle) ?></dt>
                    <dd><?= h($valeur) ?></dd>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>

    <div>
        <?= $this->Form->create($photo, ['class' => 'max-w-xl space-y-5']) ?>
            <?= $this->Form->control('titre', ['label' => $label, 'class' => $champ]) ?>
            <?= $this->Form->control('description', [
                'type' => 'textarea',
                'rows' => 4,
                'label' => $label,
                'class' => $champ,
            ]) ?>

            <div>
                <?= $this->Form->control('alt', [
                    'label' => ['text' => 'Texte alternatif'] + $label,
                    'class' => $champ,
                ]) ?>
                <p class="mt-1 text-xs text-gris">
                    Décrit l'image pour les lecteurs d'écran et les moteurs. À remplir
                    même quand la photo a déjà un titre : les deux ne disent pas la
                    même chose.
                </p>
            </div>

            <?= $this->Form->control('albums._ids', [
                'type' => 'select',
                'multiple' => 'checkbox',
                'options' => $albums,
                'label' => ['text' => 'Albums'] + $label,
            ]) ?>

            <?= $this->Form->control('tags._ids', [
                'type' => 'select',
                'multiple' => 'checkbox',
                'options' => $tags,
                'label' => ['text' => 'Tags'] + $label,
            ]) ?>

            <?= $this->Form->control('filigrane', [
                'label' => ['text' => 'Appliquer le filigrane', 'class' => 'text-sm text-gris'],
            ]) ?>
            <?= $this->Form->control('actif', [
                'label' => ['text' => 'En ligne', 'class' => 'text-sm text-gris'],
            ]) ?>

            <div class="flex items-center gap-6 pt-2">
                <?= $this->Form->button('Enregistrer', [
                    'class' => 'bg-corail px-6 py-2 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
                ]) ?>
                <a class="text-sm text-gris hover:text-corail"
                   href="<?= $this->Url->build(['action' => 'index']) ?>">Annuler</a>
            </div>
        <?= $this->Form->end() ?>

        <div class="mt-10 flex flex-wrap items-center gap-6 border-t border-white/10 pt-6 text-sm">
            <?= $this->Form->postLink('Régénérer les déclinaisons', ['action' => 'regenerer', $photo->id], [
                'class' => 'border border-white/20 px-4 py-2 uppercase tracking-titre hover:border-corail hover:text-corail',
                'confirm' => "Les fichiers dérivés seront recalculés à partir de l'original. Continuer ?",
            ]) ?>

            <?= $this->Form->postLink('Supprimer la photo', ['action' => 'supprimer', $photo->id], [
                'class' => 'text-gris hover:text-red-400',
                'confirm' => 'La photo, ses déclinaisons et son original seront supprimés. Continuer ?',
            ]) ?>
        </div>

        <p class="mt-4 text-xs text-gris">
            Régénérer sert après un changement de réglage — filigrane, nouvelle
            taille — ou si un fichier dérivé a disparu du serveur.
        </p>
    </div>
</div>
