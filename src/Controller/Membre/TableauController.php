<?php
declare(strict_types=1);

namespace App\Controller\Membre;

/**
 * Accueil de l'espace membre : ce que le client a reçu.
 */
class TableauController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $moiId = (int)$this->Authentication->getIdentity()->getIdentifier();

        // Le filtre sur le destinataire est posé dans la requête elle-même : le
        // membre ne doit jamais voir passer la livraison d'un autre client.
        $galeries = $this->fetchTable('Galeries')->find()
            ->where(['client_id' => $moiId, 'actif' => true])
            ->orderBy(['date_livraison' => 'DESC'])
            ->all();

        $moodboards = $this->fetchTable('Moodboards')->find()
            ->where(['destinataire_id' => $moiId])
            ->orderBy(['created' => 'DESC'])
            ->all();

        $this->Authorization->skipAuthorization();
        $this->set(compact('galeries', 'moodboards'));
        $this->set('title', 'Mon espace');
    }
}
