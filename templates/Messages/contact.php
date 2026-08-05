<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 * @var array $demandes
 */

$champ = 'w-full border border-white/20 bg-noir-clair px-4 py-3 focus:border-corail';
$label = 'mb-2 block text-sm text-gris';
?>
<?= $this->Seo->partage([
    'titre' => 'Contact — Chancel Photographie',
    'description' => 'Prendre contact pour un shooting, un tirage ou un projet.',
]) ?>

<section class="mx-auto max-w-2xl px-6 py-16">
    <h1 data-anim="titre" class="mb-4 text-3xl md:text-5xl">Contact</h1>
    <p data-anim class="mb-10 text-gris">
        Shooting, tirage, exposition ou simple question : écrivez-moi.
    </p>

    <?= $this->Form->create($message, ['class' => 'space-y-6']) ?>
        <div>
            <?= $this->Form->control('demande_id', [
                'type' => 'select',
                'options' => $demandes,
                'empty' => 'Choisir…',
                'label' => ['text' => 'Nature de la demande', 'class' => $label],
                'class' => $champ,
            ]) ?>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <?= $this->Form->control('nom', [
                'label' => ['text' => 'Votre nom', 'class' => $label],
                'required' => true,
                'autocomplete' => 'name',
                'class' => $champ,
            ]) ?>
            <?= $this->Form->control('email', [
                'type' => 'email',
                'label' => ['text' => 'Votre e-mail', 'class' => $label],
                'required' => true,
                'autocomplete' => 'email',
                'class' => $champ,
            ]) ?>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <?= $this->Form->control('telephone', [
                'label' => ['text' => 'Téléphone (facultatif)', 'class' => $label],
                'autocomplete' => 'tel',
                'class' => $champ,
            ]) ?>
            <?= $this->Form->control('sujet', [
                'label' => ['text' => 'Sujet', 'class' => $label],
                'class' => $champ,
            ]) ?>
        </div>

        <?= $this->Form->control('contenu', [
            'type' => 'textarea',
            'rows' => 7,
            'label' => ['text' => 'Votre message', 'class' => $label],
            'required' => true,
            'class' => $champ,
        ]) ?>

        <?php
        // Captcha mathématique + piège à robots. Pas de service tiers : rien ne
        // part chez Google, donc rien à déclarer côté RGPD.
        ?>
        <div class="border border-white/10 bg-noir-carte p-4">
            <?= $this->Captcha->render([
                'placeholder' => __('Résultat'),
                'class' => $champ,
            ]) ?>
        </div>

        <?= $this->Form->button('Envoyer', [
            'class' => 'bg-corail px-8 py-3 font-display text-sm uppercase tracking-titre text-noir hover:bg-corail-sombre',
        ]) ?>
    <?= $this->Form->end() ?>
</section>
