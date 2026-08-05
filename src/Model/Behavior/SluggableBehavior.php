<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Utility\Text;

/**
 * Génère un slug d'URL à partir d'un champ source, et garantit son unicité.
 *
 * L'ancien site utilisait `Tools.Slugged` d'un paquet tiers. Le besoin ici tient
 * en une cinquantaine de lignes : une dépendance de moins à suivre lors des
 * montées de version.
 *
 * Le slug n'est régénéré que s'il est vide : une fois publiée, une URL ne doit
 * pas changer parce que quelqu'un a corrigé une faute dans le titre — cela
 * casserait les liens entrants et le référencement. Pour forcer un nouveau slug,
 * il faut le vider explicitement.
 */
class SluggableBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'field' => 'titre',
        'slug' => 'slug',
        'maxLength' => 190,
        'replacement' => '-',
        // Champ de repli quand la source est vide. Indispensable pour les photos :
        // `titre` est facultatif alors que `slug` est NOT NULL, un import sans
        // titre échouerait sinon au niveau de la base.
        'fallbackField' => null,
    ];

    /**
     * @param \Cake\Event\EventInterface $event Événement déclencheur.
     * @param \Cake\Datasource\EntityInterface $entity Entité sur le point d'être sauvegardée.
     * @param \ArrayObject $options Options de sauvegarde.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $champSlug = $this->getConfig('slug');
        $champSource = $this->getConfig('field');

        $slugActuel = $entity->get($champSlug);

        if (!empty($slugActuel)) {
            // Un slug fourni à la main est respecté, mais toujours normalisé et
            // rendu unique : l'admin peut le choisir, pas casser les URL.
            $entity->set($champSlug, $this->rendreUnique($this->normaliser((string)$slugActuel), $entity));

            return;
        }

        $source = (string)$entity->get($champSource);

        if ($source === '') {
            $repli = $this->getConfig('fallbackField');
            $source = $repli !== null ? (string)$entity->get($repli) : '';
        }

        // Dernier recours : une chaîne aléatoire. Mieux vaut une URL laide qu'un
        // enregistrement refusé par la contrainte NOT NULL.
        if (trim($source) === '') {
            $source = 'photo-' . bin2hex(random_bytes(4));
        }

        $entity->set($champSlug, $this->rendreUnique($this->normaliser($source), $entity));
    }

    /**
     * @param string $valeur Texte à transformer.
     * @return string
     */
    protected function normaliser(string $valeur): string
    {
        $slug = Text::slug(mb_strtolower($valeur), [
            'replacement' => $this->getConfig('replacement'),
        ]);

        return mb_substr($slug, 0, (int)$this->getConfig('maxLength'));
    }

    /**
     * Ajoute un suffixe numérique tant que le slug est déjà pris.
     *
     * @param string $slug Slug candidat.
     * @param \Cake\Datasource\EntityInterface $entity Entité concernée, exclue de la recherche.
     * @return string
     */
    protected function rendreUnique(string $slug, EntityInterface $entity): string
    {
        $champSlug = $this->getConfig('slug');
        $cle = $this->_table->getPrimaryKey();
        $base = $slug;
        $suffixe = 1;

        while (true) {
            $requete = $this->_table->find()->where([$champSlug => $slug]);

            // À la modification, l'entité ne doit pas entrer en conflit avec elle-même.
            if (!$entity->isNew() && $entity->get((string)$cle) !== null) {
                $requete->where([$this->_table->aliasField((string)$cle) . ' !=' => $entity->get((string)$cle)]);
            }

            if ($requete->count() === 0) {
                return $slug;
            }

            $suffixe++;
            $slug = $base . $this->getConfig('replacement') . $suffixe;
        }
    }
}
