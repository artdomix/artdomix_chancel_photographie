<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 */
?>
NOUVEAU MESSAGE

Nom      : <?= $message->nom ?>

Courriel : <?= $message->email ?>

<?php if ($message->telephone) : ?>
Téléphone : <?= $message->telephone ?>

<?php endif; ?>
<?php if ($message->demande !== null) : ?>
Nature   : <?= $message->demande->nom ?>

<?php endif; ?>
<?php if ($message->sujet) : ?>
Sujet    : <?= $message->sujet ?>

<?php endif; ?>

<?= $message->contenu ?>


--
Répondre à ce courriel écrit directement à l'expéditeur.
Administration : <?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Messages', 'action' => 'index'], ['fullBase' => true]) ?>
