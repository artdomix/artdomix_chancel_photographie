<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 */

$gris = '#9a9a9a';
$corail = '#e85356';
?>
<h1 style="margin:0 0 20px 0;font-size:20px;font-weight:300;letter-spacing:0.12em;text-transform:uppercase;">
    Nouveau message
</h1>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="margin-bottom:24px;font-size:14px;">
    <tr>
        <td style="padding:4px 12px 4px 0;color:<?= $gris ?>;">Nom</td>
        <td style="padding:4px 0;"><?= h($message->nom) ?></td>
    </tr>
    <tr>
        <td style="padding:4px 12px 4px 0;color:<?= $gris ?>;">Courriel</td>
        <td style="padding:4px 0;">
            <a href="mailto:<?= h($message->email) ?>" style="color:<?= $corail ?>;"><?= h($message->email) ?></a>
        </td>
    </tr>
    <?php if ($message->telephone) : ?>
        <tr>
            <td style="padding:4px 12px 4px 0;color:<?= $gris ?>;">Téléphone</td>
            <td style="padding:4px 0;"><?= h($message->telephone) ?></td>
        </tr>
    <?php endif; ?>
    <?php if ($message->demande !== null) : ?>
        <tr>
            <td style="padding:4px 12px 4px 0;color:<?= $gris ?>;">Nature</td>
            <td style="padding:4px 0;"><?= h($message->demande->nom) ?></td>
        </tr>
    <?php endif; ?>
    <?php if ($message->sujet) : ?>
        <tr>
            <td style="padding:4px 12px 4px 0;color:<?= $gris ?>;">Sujet</td>
            <td style="padding:4px 0;"><?= h($message->sujet) ?></td>
        </tr>
    <?php endif; ?>
</table>

<div style="border-left:2px solid rgba(255,255,255,0.15);padding-left:16px;">
    <?= nl2br(h($message->contenu)) ?>
</div>

<p style="margin-top:28px;font-size:13px;color:<?= $gris ?>;">
    Répondre à ce courriel écrit directement à <?= h($message->nom) ?>.
    Le message est aussi consultable dans
    <a href="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Messages', 'action' => 'index'], ['fullBase' => true])) ?>"
       style="color:<?= $corail ?>;">l'administration</a>.
</p>
