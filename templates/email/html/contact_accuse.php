<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 */

$gris = '#9a9a9a';
?>
<h1 style="margin:0 0 20px 0;font-size:20px;font-weight:300;letter-spacing:0.12em;text-transform:uppercase;">
    Message bien reçu
</h1>

<p style="margin:0 0 16px 0;">Bonjour <?= h($message->nom) ?>,</p>

<p style="margin:0 0 16px 0;">
    Votre message est arrivé. Je vous réponds dès que possible, en général sous
    deux jours ouvrés.
</p>

<p style="margin:0 0 8px 0;font-size:13px;color:<?= $gris ?>;">Copie de votre message :</p>

<div style="border-left:2px solid rgba(255,255,255,0.15);padding-left:16px;font-size:14px;color:<?= $gris ?>;">
    <?= nl2br(h($message->contenu)) ?>
</div>

<p style="margin-top:28px;">Chancel</p>

<p style="margin-top:24px;font-size:12px;color:<?= $gris ?>;">
    Ce courriel est envoyé automatiquement, mais vous pouvez y répondre.
</p>
