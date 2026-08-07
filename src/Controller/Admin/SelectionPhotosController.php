<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Association\AssociateurPhotos;
use App\Service\Association\Liaison;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Rattachement des photos à un album, un moodboard ou une galerie.
 *
 * Un seul écran sert les trois cas : ils ne diffèrent que par la table de
 * jonction, décrite par `Liaison`. Écrire trois écrans identiques reviendrait à
 * créer trois endroits où corriger le même défaut.
 *
 * Les rattachements sont enregistrés **au clic**, un par requête, et non à la
 * soumission d'un formulaire d'ensemble. Deux raisons : une sélection de deux
 * cents photos n'est pas perdue si l'onglet se ferme, et un formulaire de cette
 * taille dépasserait `max_input_vars` sur un mutualisé — qui tronque la requête
 * en silence, sans erreur, en perdant les dernières photos cochées.
 */
class SelectionPhotosController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event Événement de démarrage.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Bascule, réordonnancement et couverture partent de htmx ou du
        // glisser-déposer : ils portent l'en-tête CSRF, jamais un jeton de champs.
        $this->actionsJavascript(['basculer', 'ordonner', 'couverture']);
    }

    /**
     * Écran de sélection : ce qui est retenu, et la photothèque pour compléter.
     *
     * @param string|null $type Type de liaison (« album », « moodboard »…).
     * @param string|null $id Identifiant de l'entité porteuse.
     * @param \App\Service\Association\AssociateurPhotos|null $associateur Service.
     * @return void
     */
    public function index(
        ?string $type = null,
        ?string $id = null,
        ?AssociateurPhotos $associateur = null,
    ): void {
        $associateur ??= new AssociateurPhotos();
        $liaison = Liaison::pour($type);
        $cible = $associateur->cible($liaison, (int)$id);

        $recherche = trim((string)$this->request->getQuery('q', ''));
        $filtre = $this->filtreDemande();

        $requete = $this->fetchTable('Photos')->find()->orderBy(['Photos.id' => 'DESC']);

        if ($recherche !== '') {
            $requete = $requete->find('recherche', terme: $recherche);
        }

        // Comptés avant le filtrage, mais après la recherche : « 47 à ranger »
        // doit parler de ce que l'on cherche, pas de toute la photothèque.
        $compteurs = [
            'toutes' => (clone $requete)->count(),
            'liees' => (clone $requete)->where($this->conditionLiaison($liaison, $cible->id, true))->count(),
            'libres' => (clone $requete)->where($this->conditionLiaison($liaison, $cible->id, false))->count(),
        ];

        if ($filtre !== 'toutes') {
            $requete->where($this->conditionLiaison($liaison, $cible->id, $filtre === 'liees'));
        }

        $this->set('liaison', $liaison);
        $this->set('cible', $cible);
        $this->set('liees', $associateur->photosLiees($liaison, $cible->id));
        $this->set('phototheque', $this->paginate($requete, ['limit' => 48]));
        $this->set('recherche', $recherche);
        $this->set('filtre', $filtre);
        $this->set('compteurs', $compteurs);
        $this->set('title', sprintf('Photos du %s', $liaison->libelle));

        // Recherche htmx : seul le panneau de la photothèque est renvoyé, la
        // sélection courante reste en place à l'écran.
        if ($this->request->is('htmx')) {
            $this->render('/element/admin/phototheque');
        }
    }

    /**
     * Filtre demandé, ramené à une valeur connue.
     *
     * @return string
     */
    protected function filtreDemande(): string
    {
        $filtre = (string)$this->request->getQuery('filtre', 'toutes');

        return in_array($filtre, ['toutes', 'liees', 'libres'], true) ? $filtre : 'toutes';
    }

    /**
     * Condition restreignant aux photos rattachées, ou à celles qui ne le sont pas.
     *
     * `NOT IN` est sûr ici : `photo_id` est NOT NULL dans les tables de jonction.
     * Avec une colonne nullable, un seul NULL suffirait à ne renvoyer aucune ligne.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @param bool $liees Vrai pour les photos déjà rattachées.
     * @return array<string, mixed>
     */
    protected function conditionLiaison(Liaison $liaison, int $cibleId, bool $liees): array
    {
        $sousRequete = $this->fetchTable($liaison->jonction)->find()
            ->select(['photo_id'])
            ->where([$liaison->cleEtrangere => $cibleId]);

        return ['Photos.id ' . ($liees ? 'IN' : 'NOT IN') => $sousRequete];
    }

    /**
     * Compteurs des trois onglets, sur l'ensemble de la photothèque.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @return array<string, int>
     */
    protected function compteurs(Liaison $liaison, int $cibleId): array
    {
        $photos = $this->fetchTable('Photos');

        return [
            'toutes' => $photos->find()->count(),
            'liees' => $photos->find()->where($this->conditionLiaison($liaison, $cibleId, true))->count(),
            'libres' => $photos->find()->where($this->conditionLiaison($liaison, $cibleId, false))->count(),
        ];
    }

    /**
     * Ajoute ou retire une photo de la sélection.
     *
     * @param string|null $type Type de liaison.
     * @param string|null $id Identifiant de l'entité porteuse.
     * @param string|null $photoId Identifiant de la photo.
     * @param \App\Service\Association\AssociateurPhotos|null $associateur Service.
     * @return \Cake\Http\Response|null
     */
    public function basculer(
        ?string $type = null,
        ?string $id = null,
        ?string $photoId = null,
        ?AssociateurPhotos $associateur = null,
    ): ?Response {
        $this->request->allowMethod('post');

        $associateur ??= new AssociateurPhotos();
        $liaison = Liaison::pour($type);
        $cible = $associateur->cible($liaison, (int)$id);

        $photo = $this->fetchTable('Photos')->find()->where(['Photos.id' => (int)$photoId])->firstOrFail();
        $liee = $associateur->basculer($liaison, $cible->id, $photo->id);

        if ($this->request->is('htmx')) {
            $this->set(compact('liaison', 'cible', 'photo', 'liee'));
            // Les compteurs du filtre viennent de changer : ils accompagnent la
            // vignette dans un échange hors bande, sinon ils resteraient faux
            // jusqu'au prochain rechargement.
            $this->set('compteurs', $this->compteurs($liaison, $cible->id));
            $this->set('filtre', $this->filtreDemande());
            $this->set('rafraichirOnglets', true);
            $this->viewBuilder()->disableAutoLayout();

            return $this->render('/element/admin/vignette_selectionnable');
        }

        return $this->redirect(['action' => 'index', $liaison->type, $cible->id]);
    }

    /**
     * Enregistre l'ordre d'affichage, envoyé en JSON par le glisser-déposer.
     *
     * @param string|null $type Type de liaison.
     * @param string|null $id Identifiant de l'entité porteuse.
     * @param \App\Service\Association\AssociateurPhotos|null $associateur Service.
     * @return \Cake\Http\Response
     */
    public function ordonner(
        ?string $type = null,
        ?string $id = null,
        ?AssociateurPhotos $associateur = null,
    ): Response {
        $this->request->allowMethod('post');

        $associateur ??= new AssociateurPhotos();
        $liaison = Liaison::pour($type);
        $cible = $associateur->cible($liaison, (int)$id);

        $ordre = array_map('intval', (array)($this->request->getData('ordre') ?? []));
        $nb = $associateur->ordonner($liaison, $cible->id, $ordre);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['ok' => true, 'nb' => $nb]));
    }

    /**
     * Retire plusieurs photos d'un coup, ou vide la sélection.
     *
     * @param string|null $type Type de liaison.
     * @param string|null $id Identifiant de l'entité porteuse.
     * @param \App\Service\Association\AssociateurPhotos|null $associateur Service.
     * @return \Cake\Http\Response|null
     */
    public function retirer(
        ?string $type = null,
        ?string $id = null,
        ?AssociateurPhotos $associateur = null,
    ): ?Response {
        $this->request->allowMethod('post');

        $associateur ??= new AssociateurPhotos();
        $liaison = Liaison::pour($type);
        $cible = $associateur->cible($liaison, (int)$id);

        // Sans identifiants, c'est la sélection entière qui part : le gabarit
        // demande confirmation avant d'envoyer ce cas.
        $ids = array_map('intval', (array)($this->request->getData('ids') ?? []));
        $nb = $associateur->detacherPlusieurs($liaison, $cible->id, $ids);

        $this->Flash->success($nb === 0
            ? __('Aucune photo retirée.')
            : __('{0} photo(s) retirée(s).', $nb));

        return $this->redirect(['action' => 'index', $liaison->type, $cible->id]);
    }

    /**
     * Note et mise en avant d'une photo — moodboards uniquement.
     *
     * @param string|null $type Type de liaison.
     * @param string|null $id Identifiant de l'entité porteuse.
     * @param string|null $photoId Identifiant de la photo.
     * @param \App\Service\Association\AssociateurPhotos|null $associateur Service.
     * @return \Cake\Http\Response|null
     */
    public function annoter(
        ?string $type = null,
        ?string $id = null,
        ?string $photoId = null,
        ?AssociateurPhotos $associateur = null,
    ): ?Response {
        $this->request->allowMethod('post');

        $associateur ??= new AssociateurPhotos();
        $liaison = Liaison::pour($type);
        $cible = $associateur->cible($liaison, (int)$id);

        if ($associateur->annoter($liaison, $cible->id, (int)$photoId, $this->request->getData())) {
            $this->Flash->success(__('Photo mise à jour.'));
        } else {
            $this->Flash->error(__("La photo n'a pas pu être mise à jour."));
        }

        return $this->redirect(['action' => 'index', $liaison->type, $cible->id]);
    }

    /**
     * Désigne la photo de couverture — albums uniquement.
     *
     * @param string|null $type Type de liaison.
     * @param string|null $id Identifiant de l'entité porteuse.
     * @param string|null $photoId Identifiant de la photo, ou « aucune ».
     * @param \App\Service\Association\AssociateurPhotos|null $associateur Service.
     * @return \Cake\Http\Response|null
     */
    public function couverture(
        ?string $type = null,
        ?string $id = null,
        ?string $photoId = null,
        ?AssociateurPhotos $associateur = null,
    ): ?Response {
        $this->request->allowMethod('post');

        $associateur ??= new AssociateurPhotos();
        $liaison = Liaison::pour($type);
        $cible = $associateur->cible($liaison, (int)$id);

        $associateur->definirCouverture(
            $liaison,
            $cible->id,
            $photoId === null || $photoId === 'aucune' ? null : (int)$photoId,
        );

        $this->Flash->success(__('Photo de couverture enregistrée.'));

        return $this->redirect(['action' => 'index', $liaison->type, $cible->id]);
    }
}
