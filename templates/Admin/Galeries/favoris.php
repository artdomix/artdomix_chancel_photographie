<?php
/**
 * Sélection retenue par le client.
 *
 * C'est la finalité du proofing : le photographe doit lire ce choix sans avoir
 * à se connecter au compte du client.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Galerie $galerie
 * @var iterable $photos
 */

$retenues = [];

foreach ($photos as $photo) {
    $retenues[] = $photo;
}
?>
<div class="mb-8">
    <a class="lien-souligne text-sm text-gris"
       href="<?= $this->Url->build(['action' => 'index']) ?>">← Galeries client</a>
    <h1 class="mt-2 text-2xl">Favoris — <?= h($galerie->nom) ?></h1>
    <p class="mt-2 text-sm text-gris">
        <?= h($galerie->client->email ?? 'Aucun compte associé') ?> —
        <?= h((string)count($retenues)) ?> photo(s) retenue(s)
    </p>
</div>

<?php if ($retenues === []) : ?>
    <p class="py-16 text-center text-gris">Le client n'a encore rien sélectionné.</p>
<?php else : ?>
    <?php
    // Les noms de fichiers sont repris tels quels : c'est ce qui sert au
    // photographe pour retrouver les originaux dans son catalogue.
    ?>
    <ul class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
        <?php foreach ($retenues as $photo) : ?>
            <li>
                <img src="<?= h($this->Photo->url($photo, 'thumb', 'jpeg')) ?>"
                     alt="<?= h((string)($photo->titre ?? '')) ?>" width="320" height="213"
                     loading="lazy" class="w-full object-cover">
                <p class="mt-1 truncate text-xs text-gris" title="<?= h($photo->fichier) ?>">
                    <?= h($photo->fichier) ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
