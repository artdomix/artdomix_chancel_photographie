<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Video $video
 */
?>
<?= $this->Seo->partage([
    'titre' => $video->titre . ' — Chancel Photographie',
    'description' => (string)($video->description ?? ''),
    'image' => $video->photo,
    'type' => 'video.other',
]) ?>

<section class="mx-auto max-w-4xl px-6 py-16">
    <p class="text-sm text-gris">
        <a class="lien-souligne" href="<?= $this->Url->build('/videos') ?>">← Vidéos</a>
    </p>

    <h1 data-anim="titre" class="mt-4 text-3xl md:text-5xl"><?= h($video->titre) ?></h1>

    <?php
    // L'URL est composée par l'enum à partir de la seule référence : rien de ce
    // qui est saisi en admin n'atterrit tel quel dans l'attribut `src`.
    // `youtube-nocookie` côté YouTube, et pas d'autoplay — la lecture reste un
    // choix du visiteur.
    ?>
    <div class="mt-10 aspect-video w-full bg-noir-clair">
        <iframe class="h-full w-full"
                src="<?= h($video->plateforme->urlEmbed($video->video_ref)) ?>"
                title="<?= h($video->titre) ?>"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture"
                allowfullscreen></iframe>
    </div>

    <p class="mt-3 text-sm text-gris">
        <?= h($video->typevideo->nom ?? $video->plateforme->label()) ?>
        <?php if ($video->duree !== null) : ?>
            — <?= h(sprintf('%d min', (int)ceil($video->duree / 60))) ?>
        <?php endif; ?>
    </p>

    <?php if ($video->description) : ?>
        <div class="mt-8 leading-relaxed"><?= nl2br(h($video->description)) ?></div>
    <?php endif; ?>
</section>
