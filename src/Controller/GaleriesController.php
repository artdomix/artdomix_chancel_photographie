<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Galerie;
use App\Model\Table\GaleriesTable;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Utility\Security;
use ZipArchive;

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
    public function partage(?string $token = null): ?Response
    {
        $galerie = $this->chargerParToken($token);

        if ($galerie->estProtege() && !$this->estDeverrouillee($galerie)) {
            $this->set('galerie', $galerie);
            $this->set('title', 'Galerie protégée');
            $this->viewBuilder()->setLayout('partage');

            return $this->render('mot_de_passe');
        }

        $this->set('galerie', $galerie);
        $this->set('favoris', $this->favorisDuVisiteur($galerie));
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
    public function favori(?string $token = null, ?string $photoId = null): ?Response
    {
        $this->request->allowMethod('post');

        $galerie = $this->chargerParToken($token);

        if ($galerie->estProtege() && !$this->estDeverrouillee($galerie)) {
            throw new NotFoundException();
        }

        // La photo doit appartenir à CETTE galerie : sans ce contrôle, un
        // identifiant arbitraire permettrait de marquer n'importe quelle photo
        // du site depuis n'importe quel lien.
        $photo = $this->Galeries->Photos->find()
            ->matching('Galeries', fn($q) => $q->where(['Galeries.id' => $galerie->id]))
            ->where(['Photos.id' => (int)$photoId])
            ->first();

        if ($photo === null) {
            throw new NotFoundException();
        }

        $favoris = $this->fetchTable('GalerieFavoris');
        $identite = $this->Authentication->getIdentity();
        $conditions = [
            'galerie_id' => $galerie->id,
            'photo_id' => $photo->id,
        ];

        if ($identite !== null) {
            $conditions['user_id'] = $identite->getIdentifier();
        } else {
            $conditions['session_key'] = $this->cleVisiteur();
        }

        $existant = $favoris->find()->where($conditions)->first();
        $actif = false;

        if ($existant !== null) {
            $favoris->delete($existant);
        } else {
            $atteint = $galerie->quota_favoris > 0
                && count($this->favorisDuVisiteur($galerie)) >= $galerie->quota_favoris;

            if ($atteint) {
                $this->Flash->error(__(
                    'Vous avez atteint la limite de {0} photos.',
                    $galerie->quota_favoris,
                ));
            } else {
                $favoris->saveOrFail($favoris->newEntity($conditions));
                $actif = true;
            }
        }

        if ($this->request->is('htmx')) {
            $this->set(compact('galerie', 'photo', 'actif'));
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
    public function telecharger(?string $token = null): Response
    {
        $galerie = $this->chargerParToken($token);

        if ($galerie->estProtege() && !$this->estDeverrouillee($galerie)) {
            throw new NotFoundException();
        }

        if (!$galerie->telechargement_actif) {
            throw new NotFoundException();
        }

        $favoris = $this->favorisDuVisiteur($galerie);

        // Rien de retenu : on livre la galerie entière plutôt qu'une archive
        // vide, qui n'aiderait personne.
        $aLivrer = $favoris === []
            ? $galerie->photos
            : array_filter($galerie->photos, fn($photo) => in_array($photo->id, $favoris, true));

        if ($aLivrer === []) {
            throw new NotFoundException();
        }

        $archive = new ZipArchive();
        $chemin = TMP . 'galerie-' . $galerie->id . '-' . bin2hex(Security::randomBytes(8)) . '.zip';

        if ($archive->open($chemin, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new NotFoundException();
        }

        foreach ($aLivrer as $photo) {
            $source = WWW_ROOT . 'media' . DS . 'photos' . DS . $photo->fichier . '-large.jpeg';

            if (is_file($source)) {
                // Nom lisible dans l'archive plutôt que l'UUID interne : le
                // client doit pouvoir s'y retrouver.
                $archive->addFile($source, sprintf('%s-%s.jpg', $galerie->slug, $photo->slug));
            }
        }

        $archive->close();

        // `withFile()` supprime le fichier temporaire après l'envoi.
        return $this->response
            ->withFile($chemin, [
                'download' => true,
                'name' => $galerie->slug . '.zip',
            ])
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
     * Identifiants des photos retenues par le visiteur courant.
     *
     * @param \App\Model\Entity\Galerie $galerie Galerie concernée.
     * @return list<int>
     */
    protected function favorisDuVisiteur(Galerie $galerie): array
    {
        $identite = $this->Authentication->getIdentity();
        $conditions = ['galerie_id' => $galerie->id];

        if ($identite !== null) {
            $conditions['user_id'] = $identite->getIdentifier();
        } else {
            $conditions['session_key'] = $this->cleVisiteur();
        }

        return $this->fetchTable('GalerieFavoris')->find()
            ->where($conditions)
            ->all()
            ->extract('photo_id')
            ->toList();
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
