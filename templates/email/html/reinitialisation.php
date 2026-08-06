<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $utilisateur
 * @var string $lien
 */

$gris = '#9a9a9a';
$corail = '#e85356';
?>
<h1 style="margin:0 0 20px 0;font-size:20px;font-weight:300;letter-spacing:0.12em;text-transform:uppercase;">
    Nouveau mot de passe
</h1>

<p style="margin:0 0 16px 0;">Bonjour,</p>

<p style="margin:0 0 24px 0;">
    Vous avez demandé à choisir un nouveau mot de passe. Ce lien est valable
    <strong>deux heures</strong> et ne fonctionne qu'une fois.
</p>

<p style="margin:0 0 24px 0;">
    <a href="<?= h($lien) ?>"
       style="display:inline-block;padding:12px 24px;background-color:<?= $corail ?>;color:#0d0d0d;
              text-decoration:none;font-size:13px;letter-spacing:0.12em;text-transform:uppercase;">
        Choisir mon mot de passe
    </a>
</p>

<?php // Le lien est répété en clair : certains clients de messagerie n'affichent pas les boutons. ?>
<p style="margin:0 0 24px 0;font-size:12px;color:<?= $gris ?>;word-break:break-all;">
    Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :<br>
    <?= h($lien) ?>
</p>

<p style="margin:0;font-size:13px;color:<?= $gris ?>;">
    Si vous n'êtes pas à l'origine de cette demande, ignorez ce courriel : votre
    mot de passe actuel reste valable.
</p>
