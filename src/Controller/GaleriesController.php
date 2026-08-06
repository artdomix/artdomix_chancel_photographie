<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Galerie;
use App\Model\Table\GaleriesTable;
use App\Service\Galerie\SelectionClient;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Utility\Security;

/**
 * Galerie client accessible par lien de partage.
 *
 * Même principe que les moodboards : le client reçoit une URL, pas un compte.
 * La différence tient à l'usage — ici le client **sélectionne** ses photos, et
 * cette sélection doit lui être attribuée. Comme il n'est pas identifié, on lui
 * pose une clé aléatoire en session qui joue le rôle d'identité anonyme.
 *
 * @property \App\Model\Table\GaleriesTable $Galeries
 */
class GaleriesController extends AppController
{
    protected const SESSION_DEVERROUILLEES = 'Galeries.deverrouillees';

    protected const SESSION_CLE_VISITEUR = 'Galeries.cleVisiteur';

    /**
     * Déclarée explicitement : depuis PHP 8.2, affecter une propriété non
     * déclarée émet une dépréciation.
     *
     * @var \App\Model\Table\GaleriesTable
     */
    protected GaleriesTable $Galeries;

    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Galeries = $this->fetchTable('Galeries');
        $this->autoriserPublic(['partage', 'deverrouiller', 'favori', 'telecharger']);

        // La bascule d'un favori part d'un bouton htmx, pas d'un formulaire :
        // elle porte l'en-tête CSRF mais aucun jeton de champs.
        $this->actionsJavascript(['favori']);
    }

    /**
     * @param string|null $token Jeton de partage.
     * @return \Cake\Http\Response|null
     */
    public function partage(?string $token = null, ?SelectionClient $selection = null): ?Response
    {
        $selection ??= new SelectionClient();
        $galerie = $this->chargerParToken($token);

        if ($galerie->estProtege() && !$this->estDeverrouillee($galerie)) {
            $this->set('galerie', $galerie);
            $this->set('title', 'Galerie protégée');
            $this->viewBuilder()->setLayout('partage');

            return $this->render('mot_de_passe');
        }

        $this->set('galerie', $galerie);
        $this->set('favoris', $selection->photosRetenues($galerie, $this->identiteVisiteur()));
        $this->set('title', $galerie->nom);
        $this->viewBuilder()->setLayout('partage');

        return null;
    }

    /**
     * @param string|null $token Jeton de partage.
     * @return \Cake\Http\Response|null
     */
    public function deverrouiller(?string $token = null): ?Response
    {
        $this->request->allowMethod('post');

        $galerie = $this->chargerParToken($token);

        if ($galerie->verifierMotDePasse((string)$this->request->getData('password'))) {
            $session = $this->request->getSession();
            $deverrouillees = $session->read(self::SESSION_DEVERROUILLEES, []);
            $deverrouillees[$galerie->id] = true;
            $session->write(self::SESSION_DEVERROUILLEES, $deverrouillees);

            return $this->redirect(['action' => 'partage', $token]);
        }

        usleep(400_000);
        $this->Flash->error(__('Mot de passe incorrect.'));

        return $this->redirect(['action' => 'partage', $token]);
    }

    /**
     * Bascule une photo en favori. Appelé par htmx, renvoie le bouton mis à jour.
     *
     * @param string|null $token Jeton de partage.
     * @param string|null $photoId Identifiant de la photo.
     * @return \Cake\Http\Response|null
     */
    public function favori(
        ?string $token = null,
        ?string $photoId = null,
        ?SelectionClient $selection = null,
    ): ?Response {
        $this->request->allowMethod('post');

        $selection ??= new SelectionClient();
        $galerie = $this->chargerParToken($token);

        if ($galerie->estProtege() && !$this->estDeverrouillee($galerie)) {
            throw new NotFoundException();
        }

        $photo = $selection->photoDeLaGalerie($galerie, (int)$photoId);

        if ($photo === null) {
            throw new NotFoundException();
        }

        $resultat = $selection->basculer($galerie, $photo, $this->identiteVisiteur());

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
            $this->viewBuilder()->disableAutoLayout();

            return $this->render('/element/bouton_favori');
        }

        return $this->redirect(['action' => 'partage', $token]);
    }

    /**
     * Archive ZIP des photos retenues par le client.
     *
     * Ce sont les dérivés `large` (2048 px) qui sont livrés, jamais les
     * originaux : ceux-ci restent hors de `webroot` et ne sortent du serveur
     * que par un envoi manuel du photographe.
     *
     * @param string|null $token Jeton de partage.
     * @return \Cake\Http\Response
     */
    public function telecharger(?string $token = null, ?SelectionClient $selection = null): Response
    {
        $selection ??= new SelectionClient();
        $galerie = $this->chargerParToken($token);

        if ($galerie->estProtege() && !$this->estDeverrouillee($galerie)) {
            throw new NotFoundException();
        }

        if (!$galerie->telechargement_actif) {
            throw new NotFoundException();
        }

        $visiteur = $this->identiteVisiteur();
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
     * @param string|null $token Jeton de partage.
     * @return \App\Model\Entity\Galerie
     */
    protected function chargerParToken(?string $token): Galerie
    {
        if ($token === null || $token === '') {
            throw new NotFoundException();
        }

        $galerie = $this->Galeries->find()
            ->where(['Galeries.share_token' => $token, 'Galeries.actif' => true])
            ->contain(['Photos' => fn($q) => $q->orderBy(['GaleriesPhotos.ordre' => 'ASC'])])
            ->first();

        if ($galerie === null || $galerie->estExpire()) {
            throw new NotFoundException();
        }

        return $galerie;
    }

    /**
     * Identité du visiteur, sous forme de conditions de requête.
     *
     * Un client connecté est identifié par son compte ; un visiteur arrivé par
     * lien ne l'est que par une clé de session, tirée au sort à sa première
     * sélection. C'est ce qui permet à un inconnu de retrouver ses choix en
     * revenant sur la page.
     *
     * @return array<string, mixed>
     */
    protected function identiteVisiteur(): array
    {
        $identite = $this->Authentication->getIdentity();

        if ($identite !== null) {
            return ['user_id' => $identite->getIdentifier()];
        }

        return ['session_key' => $this->cleVisiteur()];
    }

    /**
     * Clé anonyme du visiteur, créée à la première sélection.
     *
     * @return string
     */
    protected function cleVisiteur(): string
    {
        $session = $this->request->getSession();
        $cle = $session->read(self::SESSION_CLE_VISITEUR);

        if (!is_string($cle) || $cle === '') {
            $cle = bin2hex(Security::randomBytes(16));
            $session->write(self::SESSION_CLE_VISITEUR, $cle);
        }

        return $cle;
    }

    /**
     * @param \App\Model\Entity\Galerie $galerie Galerie concernée.
     * @return bool
     */
    protected function estDeverrouillee(Galerie $galerie): bool
    {
        $deverrouillees = $this->request->getSession()->read(self::SESSION_DEVERROUILLEES, []);

        return !empty($deverrouillees[$galerie->id]);
    }
}
