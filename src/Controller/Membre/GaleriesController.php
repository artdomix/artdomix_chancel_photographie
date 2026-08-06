<?php
declare(strict_types=1);

namespace App\Controller\Membre;

use App\Model\Entity\Galerie;
use App\Service\Galerie\SelectionClient;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Consultation des galeries livrées, côté membre connecté.
 *
 * Contrairement à l'accès par lien, chaque action passe ici par `GaleriePolicy` :
 * être connecté ne suffit pas, encore faut-il être le client destinataire. C'est
 * ce qui empêche un membre de lire la livraison d'un autre en changeant
 * l'identifiant dans l'URL.
 *
 * C'est aussi pourquoi ces actions existent, plutôt que de renvoyer vers les URL
 * en `/g/{jeton}` : celles-ci exigent en plus le mot de passe de la galerie, que
 * le client n'a aucune raison de connaître quand il arrive par son compte. Le
 * compte *est* son autorisation.
 */
class GaleriesController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // La bascule d'un favori part d'un bouton htmx : elle porte l'en-tête
        // CSRF mais aucun jeton de champs.
        $this->actionsJavascript(['favori']);
    }

    /**
     * @param string|null $id Identifiant de la galerie.
     * @param \App\Service\Galerie\SelectionClient|null $selection Service de sélection.
     * @return void
     */
    public function voir(?string $id = null, ?SelectionClient $selection = null): void
    {
        $selection ??= new SelectionClient();
        $galerie = $this->chargerGalerie($id);

        // La policy tranche : admin, ou client destinataire d'une galerie active
        // et non expirée.
        $this->Authorization->authorize($galerie, 'view');

        $this->set('galerie', $galerie);
        $this->set('favoris', $selection->photosRetenues($galerie, $this->identiteMembre()));
        $this->set('commentaire', $this->fetchTable('GalerieCommentaires')->newEmptyEntity());
        $this->set('peutTelecharger', $this->Authorization->can($galerie, 'telecharger'));
        $this->set('peutChoisir', $this->Authorization->can($galerie, 'choisirFavoris'));
        $this->set('title', $galerie->nom);
    }

    /**
     * Retient ou retire une photo. Appelé par htmx, renvoie le bouton à jour.
     *
     * @param string|null $id Identifiant de la galerie.
     * @param string|null $photoId Identifiant de la photo.
     * @param \App\Service\Galerie\SelectionClient|null $selection Service de sélection.
     * @return \Cake\Http\Response|null
     */
    public function favori(
        ?string $id = null,
        ?string $photoId = null,
        ?SelectionClient $selection = null,
    ): ?Response {
        $this->request->allowMethod('post');

        $selection ??= new SelectionClient();
        $galerie = $this->chargerGalerie($id);

        // `choisirFavoris` et non `view` : le photographe peut consulter une
        // livraison, mais choisir à la place de son client n'aurait aucun sens.
        $this->Authorization->authorize($galerie, 'choisirFavoris');

        $photo = $selection->photoDeLaGalerie($galerie, (int)$photoId);

        if ($photo === null) {
            throw new NotFoundException();
        }

        $resultat = $selection->basculer($galerie, $photo, $this->identiteMembre());

        if ($resultat->quotaAtteint) {
            $this->Flash->error(__(
                'Vous avez atteint la limite de {0} photos.',
                $galerie->quota_favoris,
            ));
        }

        if ($this->request->is('htmx')) {
            $this->set('galerie', $galerie);
            $this->set('photo', $photo);
            $this->set('actif', $resultat->retenue);
            // Le bouton renvoyé doit continuer de pointer vers l'espace membre :
            // sans cette URL, il retomberait sur la route par jeton au clic
            // suivant, et le client se heurterait au mot de passe.
            $this->set('url', [
                'prefix' => 'Membre',
                'controller' => 'Galeries',
                'action' => 'favori',
                $galerie->id,
                $photo->id,
            ]);
            $this->viewBuilder()->disableAutoLayout();

            return $this->render('/element/bouton_favori');
        }

        return $this->redirect(['action' => 'voir', $galerie->id]);
    }

    /**
     * Archive ZIP de la sélection.
     *
     * @param string|null $id Identifiant de la galerie.
     * @param \App\Service\Galerie\SelectionClient|null $selection Service de sélection.
     * @return \Cake\Http\Response
     */
    public function telecharger(?string $id = null, ?SelectionClient $selection = null): Response
    {
        $selection ??= new SelectionClient();
        $galerie = $this->chargerGalerie($id);

        // La policy vérifie que le photographe a bien ouvert le téléchargement :
        // c'est souvent ce qui distingue une livraison payée d'une épreuve.
        $this->Authorization->authorize($galerie, 'telecharger');

        $visiteur = $this->identiteMembre();
        $photos = $selection->photosALivrer($galerie, $selection->photosRetenues($galerie, $visiteur));
        $chemin = $selection->archiver($galerie, $photos);

        if ($chemin === null) {
            throw new NotFoundException();
        }

        // `withFile()` supprime le fichier temporaire après l'envoi.
        return $this->response
            ->withFile($chemin, ['download' => true, 'name' => $galerie->slug . '.zip'])
            ->withHeader('Content-Type', 'application/zip');
    }

    /**
     * Dépose un retour sur la livraison.
     *
     * Les échanges autour d'une sélection — « la troisième est trop sombre » —
     * ont leur place à côté des photos, pas dans un fil de courriels où ils se
     * perdent. La table existait depuis le premier schéma sans que rien ne
     * l'alimente.
     *
     * @param string|null $id Identifiant de la galerie.
     * @return \Cake\Http\Response|null
     */
    public function commenter(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');

        $galerie = $this->chargerGalerie($id);
        $this->Authorization->authorize($galerie, 'view');

        $commentaires = $this->fetchTable('GalerieCommentaires');
        $commentaire = $commentaires->newEntity($this->request->getData());
        $commentaire->galerie_id = $galerie->id;
        $commentaire->user_id = $this->Authentication->getIdentity()->getIdentifier();

        if ($commentaires->save($commentaire)) {
            $this->Flash->success(__('Votre retour a bien été transmis.'));
        } else {
            $this->Flash->error(__("Votre retour n'a pas pu être enregistré."));
        }

        return $this->redirect(['action' => 'voir', $galerie->id]);
    }

    /**
     * @param string|null $id Identifiant de la galerie.
     * @return \App\Model\Entity\Galerie
     */
    protected function chargerGalerie(?string $id): Galerie
    {
        /** @var \App\Model\Entity\Galerie|null $galerie */
        $galerie = $this->fetchTable('Galeries')->find()
            ->where(['Galeries.id' => (int)$id])
            ->contain([
                'Photos' => fn($q) => $q->orderBy(['GaleriesPhotos.ordre' => 'ASC']),
                'GalerieCommentaires' => fn($q) => $q->contain(['Users'])
                    ->orderBy(['GalerieCommentaires.created' => 'ASC']),
            ])
            ->first();

        if ($galerie === null) {
            throw new NotFoundException();
        }

        return $galerie;
    }

    /**
     * Dans l'espace membre, le visiteur est toujours identifié par son compte :
     * la clé de session anonyme du chemin par lien n'a pas cours ici.
     *
     * @return array<string, mixed>
     */
    protected function identiteMembre(): array
    {
        return ['user_id' => $this->Authentication->getIdentity()->getIdentifier()];
    }
}
