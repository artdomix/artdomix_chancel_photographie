<?php
/**
 * Enveloppe des courriels HTML.
 *
 * Styles en attributs `style` et mise en page en tableau : les clients de
 * messagerie ignorent largement les feuilles externes, et Outlook ne connaît ni
 * flexbox ni grid. Le thème du site est repris à la main — Tailwind est compilé
 * dans le navigateur, il n'a aucun moyen d'agir ici.
 *
 * @var \App\View\AppView $this
 */

$fond = '#0d0d0d';
$carte = '#1a1a1a';
$texte = '#f4f2f0';
$gris = '#9a9a9a';
$corail = '#e85356';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($this->fetch('title')) ?></title>
</head>
<body style="margin:0;padding:0;background-color:<?= $fond ?>;color:<?= $texte ?>;
             font-family:'Open Sans',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
           style="background-color:<?= $fond ?>;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                       style="max-width:560px;background-color:<?= $carte ?>;border:1px solid rgba(255,255,255,0.1);">
                    <tr>
                        <td style="padding:28px 32px 8px 32px;">
                            <p style="margin:0;font-size:18px;letter-spacing:0.18em;text-transform:uppercase;
                                      color:<?= $texte ?>;">
                                Chancel
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 32px 32px 32px;color:<?= $texte ?>;">
                            <?= $this->fetch('content') ?>
                        </td>
                    </tr>
                </table>

                <p style="max-width:560px;margin:16px auto 0 auto;font-size:12px;color:<?= $gris ?>;text-align:center;">
                    Chancel Photographie —
                    <a href="<?= h($this->Url->build('/', ['fullBase' => true])) ?>"
                       style="color:<?= $corail ?>;">chancel.art-domix.fr</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
