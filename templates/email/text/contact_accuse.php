<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 */
?>
MESSAGE BIEN REÇU

Bonjour <?= $message->nom ?>,

Votre message est arrivé. Je vous réponds dès que possible, en général sous
deux jours ouvrés.

Copie de votre message :

<?= $message->contenu ?>


Chancel

--
Ce courriel est envoyé automatiquement, mais vous pouvez y répondre.
