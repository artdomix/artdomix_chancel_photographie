<?php
declare(strict_types=1);

namespace App\Service\Association;

use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Rattache des photos à un album, un moodboard ou une galerie.
 *
 * Le service travaille directement sur la table de jonction plutôt que par
 * `link()` / `unlink()` de l'association : ces méthodes ignorent les colonnes
 * portées par la jonction, et c'est justement `ordre` — présent partout — qui
 * détermine l'affichage côté public.
 */
class AssociateurPhotos
{
    use LocatorAwareTrait;

    /**
     * Charge l'entité porteuse, avec ses photos dans l'ordre.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @return \Cake\Datasource\EntityInterface
     */
    public function cible(Liaison $liaison, int $cibleId): EntityInterface
    {
        $table = $this->fetchTable($liaison->cible);

        $entite = $table->find()
            ->where([$table->aliasField('id') => $cibleId])
            ->contain(['Photos' => fn($q) => $q->orderBy([$liaison->jonction . '.ordre' => 'ASC'])])
            ->first();

        if ($entite === null) {
            throw new NotFoundException();
        }

        return $entite;
    }

    /**
     * Identifiants des photos déjà rattachées.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @return list<int>
     */
    public function photosLiees(Liaison $liaison, int $cibleId): array
    {
        return $this->fetchTable($liaison->jonction)->find()
            ->where([$liaison->cleEtrangere => $cibleId])
            ->orderBy(['ordre' => 'ASC'])
            ->all()
            ->extract('photo_id')
            ->toList();
    }

    /**
     * Ajoute une photo si elle est absente, la retire sinon.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @param int $photoId Identifiant de la photo.
     * @return bool La photo est-elle rattachée à l'issue de l'opération ?
     */
    public function basculer(Liaison $liaison, int $cibleId, int $photoId): bool
    {
        $jonction = $this->fetchTable($liaison->jonction);
        $conditions = [$liaison->cleEtrangere => $cibleId, 'photo_id' => $photoId];

        $existante = $jonction->find()->where($conditions)->first();

        if ($existante !== null) {
            $jonction->delete($existante);

            return false;
        }

        // La nouvelle photo se place en fin de sélection : c'est l'ordre dans
        // lequel le photographe l'a choisie, et il peut le corriger ensuite.
        $jonction->saveOrFail($jonction->newEntity(
            $conditions + ['ordre' => $this->prochainOrdre($liaison, $cibleId)],
        ));

        return true;
    }

    /**
     * Rattache plusieurs photos d'un coup, sans toucher à celles déjà présentes.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @param list<int> $photoIds Identifiants à rattacher.
     * @return int Nombre de photos effectivement ajoutées.
     */
    public function attacherPlusieurs(Liaison $liaison, int $cibleId, array $photoIds): int
    {
        $photoIds = array_values(array_unique(array_filter($photoIds)));

        if ($photoIds === []) {
            return 0;
        }

        $jonction = $this->fetchTable($liaison->jonction);

        // Les liaisons déjà en place sont écartées : l'index unique rejetterait
        // le doublon et ferait échouer tout le lot.
        $dejaLa = $jonction->find()
            ->where([$liaison->cleEtrangere => $cibleId, 'photo_id IN' => $photoIds])
            ->all()
            ->extract('photo_id')
            ->toList();

        $aAjouter = array_values(array_diff($photoIds, $dejaLa));

        if ($aAjouter === []) {
            return 0;
        }

        $ordre = $this->prochainOrdre($liaison, $cibleId);
        $lignes = [];

        foreach ($aAjouter as $photoId) {
            $lignes[] = [
                $liaison->cleEtrangere => $cibleId,
                'photo_id' => (int)$photoId,
                'ordre' => $ordre++,
            ];
        }

        $jonction->saveManyOrFail($jonction->newEntities($lignes));

        return count($lignes);
    }

    /**
     * Retire plusieurs photos de la sélection.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @param list<int> $photoIds Identifiants à retirer. Vide = tout retirer.
     * @return int Nombre de liaisons supprimées.
     */
    public function detacherPlusieurs(Liaison $liaison, int $cibleId, array $photoIds): int
    {
        $conditions = [$liaison->cleEtrangere => $cibleId];
        $photoIds = array_values(array_unique(array_filter($photoIds)));

        if ($photoIds !== []) {
            $conditions['photo_id IN'] = $photoIds;
        }

        return $this->fetchTable($liaison->jonction)->deleteAll($conditions);
    }

    /**
     * Enregistre un nouvel ordre d'affichage.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @param list<int> $photoIds Identifiants dans leur ordre voulu.
     * @return int Nombre de lignes réordonnées.
     */
    public function ordonner(Liaison $liaison, int $cibleId, array $photoIds): int
    {
        $jonction = $this->fetchTable($liaison->jonction);
        $reordonnees = 0;

        foreach (array_values($photoIds) as $position => $photoId) {
            $reordonnees += $jonction->updateAll(
                ['ordre' => $position],
                [$liaison->cleEtrangere => $cibleId, 'photo_id' => (int)$photoId],
            );
        }

        return $reordonnees;
    }

    /**
     * Met à jour les colonnes annexes d'une liaison — note, mise en avant.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @param int $photoId Identifiant de la photo.
     * @param array<string, mixed> $valeurs Valeurs soumises.
     * @return bool
     */
    public function annoter(Liaison $liaison, int $cibleId, int $photoId, array $valeurs): bool
    {
        if (!$liaison->estAnnotable()) {
            throw new NotFoundException();
        }

        $jonction = $this->fetchTable($liaison->jonction);
        $ligne = $jonction->find()
            ->where([$liaison->cleEtrangere => $cibleId, 'photo_id' => $photoId])
            ->first();

        if ($ligne === null) {
            throw new NotFoundException();
        }

        // Liste blanche, et passage par `patchEntity` plutôt que `set()` : le
        // marshalling convertit la case à cocher en booléen et la validation
        // s'applique. Écrire directement poserait `null` dans une colonne NOT
        // NULL dès que la case est décochée.
        $jonction->patchEntity($ligne, array_intersect_key(
            $valeurs,
            array_flip($liaison->champsAnnexes),
        ));

        return (bool)$jonction->save($ligne);
    }

    /**
     * Désigne la photo de couverture de l'entité porteuse.
     *
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @param int|null $photoId Photo choisie, ou null pour retirer la couverture.
     * @return bool
     */
    public function definirCouverture(Liaison $liaison, int $cibleId, ?int $photoId): bool
    {
        if ($liaison->couverture === null) {
            throw new NotFoundException();
        }

        // Une couverture doit faire partie de la sélection : afficher en vitrine
        // une photo absente de l'album désorienterait le visiteur qui clique.
        if ($photoId !== null && !in_array($photoId, $this->photosLiees($liaison, $cibleId), true)) {
            throw new NotFoundException();
        }

        return (bool)$this->fetchTable($liaison->cible)->updateAll(
            [$liaison->couverture => $photoId],
            ['id' => $cibleId],
        );
    }

    /**
     * @param \App\Service\Association\Liaison $liaison Liaison concernée.
     * @param int $cibleId Identifiant de l'entité porteuse.
     * @return int
     */
    protected function prochainOrdre(Liaison $liaison, int $cibleId): int
    {
        $max = $this->fetchTable($liaison->jonction)->find()
            ->where([$liaison->cleEtrangere => $cibleId])
            ->select(['maxi' => 'MAX(ordre)'])
            ->first();

        return (int)($max->maxi ?? -1) + 1;
    }
}
