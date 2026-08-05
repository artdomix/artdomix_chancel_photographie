<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Photos Model
 *
 * @property \App\Model\Table\ExifsTable&\Cake\ORM\Association\HasOne $Exifs
 * @property \App\Model\Table\ArticlesTable&\Cake\ORM\Association\HasMany $Articles
 * @property \App\Model\Table\ExpositionsTable&\Cake\ORM\Association\HasMany $Expositions
 * @property \App\Model\Table\GalerieCommentairesTable&\Cake\ORM\Association\HasMany $GalerieCommentaires
 * @property \App\Model\Table\GalerieFavorisTable&\Cake\ORM\Association\HasMany $GalerieFavoris
 * @property \App\Model\Table\LivresTable&\Cake\ORM\Association\HasMany $Livres
 * @property \App\Model\Table\ModelesTable&\Cake\ORM\Association\HasMany $Modeles
 * @property \App\Model\Table\MoodboardCommentairesTable&\Cake\ORM\Association\HasMany $MoodboardCommentaires
 * @property \App\Model\Table\ShootingsTable&\Cake\ORM\Association\HasMany $Shootings
 * @property \App\Model\Table\TiragesTable&\Cake\ORM\Association\HasMany $Tirages
 * @property \App\Model\Table\VideosTable&\Cake\ORM\Association\HasMany $Videos
 * @property \App\Model\Table\AlbumsTable&\Cake\ORM\Association\BelongsToMany $Albums
 * @property \App\Model\Table\GaleriesTable&\Cake\ORM\Association\BelongsToMany $Galeries
 * @property \App\Model\Table\MoodboardsTable&\Cake\ORM\Association\BelongsToMany $Moodboards
 * @property \App\Model\Table\TagsTable&\Cake\ORM\Association\BelongsToMany $Tags
 * @method \App\Model\Entity\Photo newEmptyEntity()
 * @method \App\Model\Entity\Photo newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Photo> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Photo get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Photo findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Photo patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Photo> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Photo|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Photo saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Photo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Photo>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Photo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Photo> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Photo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Photo>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Photo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Photo> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PhotosTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('photos');
        $this->setDisplayField('titre');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', ['field' => 'titre', 'fallbackField' => 'fichier']);

        $this->hasOne('Exifs', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Articles', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Expositions', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('GalerieCommentaires', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('GalerieFavoris', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Livres', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Modeles', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('MoodboardCommentaires', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Shootings', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Tirages', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Videos', [
            'foreignKey' => 'photo_id',
        ]);
        $this->belongsToMany('Albums', [
            'foreignKey' => 'photo_id',
            'targetForeignKey' => 'album_id',
            'joinTable' => 'albums_photos',
        ]);
        $this->belongsToMany('Galeries', [
            'foreignKey' => 'photo_id',
            'targetForeignKey' => 'galerie_id',
            'joinTable' => 'galeries_photos',
        ]);
        $this->belongsToMany('Moodboards', [
            'foreignKey' => 'photo_id',
            'targetForeignKey' => 'moodboard_id',
            'joinTable' => 'moodboards_photos',
        ]);
        $this->belongsToMany('Tags', [
            'foreignKey' => 'photo_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'photos_tags',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 36)
            ->requirePresence('uuid', 'create')
            ->notEmptyString('uuid')
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('titre')
            ->maxLength('titre', 190)
            ->allowEmptyString('titre');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 190)
            ->allowEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('alt')
            ->maxLength('alt', 255)
            ->allowEmptyString('alt');

        $validator
            ->scalar('fichier')
            ->maxLength('fichier', 100)
            ->requirePresence('fichier', 'create')
            ->notEmptyString('fichier');

        $validator
            ->scalar('extension_origine')
            ->maxLength('extension_origine', 10)
            ->allowEmptyString('extension_origine');

        $validator
            ->integer('largeur')
            ->allowEmptyString('largeur');

        $validator
            ->integer('hauteur')
            ->allowEmptyString('hauteur');

        $validator
            ->integer('poids_octets')
            ->allowEmptyString('poids_octets');

        $validator
            ->scalar('couleur_dominante')
            ->maxLength('couleur_dominante', 7)
            ->allowEmptyString('couleur_dominante');

        $validator
            ->scalar('lqip')
            ->allowEmptyString('lqip');

        $validator
            ->boolean('has_avif')
            ->notEmptyString('has_avif');

        $validator
            ->boolean('has_webp')
            ->notEmptyString('has_webp');

        $validator
            ->boolean('filigrane')
            ->notEmptyString('filigrane');

        $validator
            ->boolean('actif')
            ->notEmptyString('actif');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug']), ['errorField' => 'slug']);
        $rules->add($rules->isUnique(['uuid']), ['errorField' => 'uuid']);

        return $rules;
    }

    /**
     * Photos visibles publiquement.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Requête à filtrer.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActives(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('actif') => true]);
    }

    /**
     * Tri chronologique sur la date de prise de vue, pas sur la date d'import.
     *
     * Un photographe verse souvent ses archives longtemps après les avoir prises :
     * trier sur `created` mélangerait les séries. Les photos sans EXIF exploitable
     * retombent sur leur date de création pour ne pas disparaître du tri.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Requête à ordonner.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findChronologique(SelectQuery $query): SelectQuery
    {
        return $query
            ->contain(['Exifs'])
            ->leftJoinWith('Exifs')
            ->orderBy([
                'COALESCE(Exifs.date_capture, Photos.created)' => 'DESC',
                $this->aliasField('id') => 'DESC',
            ]);
    }

    /**
     * Recherche plein texte sur le titre et la description.
     *
     * S'appuie sur l'index FULLTEXT `ft_photos_recherche`. En mode booléen avec
     * un `*` final, la recherche fonctionne aussi sur un début de mot, ce que
     * la recherche naturelle de MySQL ne fait pas.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Requête à filtrer.
     * @param string $terme Termes saisis par le visiteur.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findRecherche(SelectQuery $query, string $terme = ''): SelectQuery
    {
        $terme = trim($terme);

        if ($terme === '') {
            return $query;
        }

        $mots = preg_split('/\s+/', $terme) ?: [];
        $expression = implode(' ', array_map(
            // Les opérateurs booléens saisis par l'utilisateur sont neutralisés :
            // un `+` ou un `-` égaré ferait échouer la requête MySQL.
            static fn(string $mot): string => '+' . preg_replace('/[+\-><()~*"@]+/', '', $mot) . '*',
            $mots,
        ));

        // Le paramètre est lié sur la requête et non sur l'expression : c'est la
        // requête qui porte les liaisons. Passer par une liaison plutôt que par
        // une concaténation ferme aussi la porte à l'injection SQL.
        return $query
            ->where(new QueryExpression(
                'MATCH (Photos.titre, Photos.description) AGAINST (:terme IN BOOLEAN MODE)',
            ))
            ->bind(':terme', $expression, 'string');
    }

    /**
     * Photos géolocalisées, pour la carte.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Requête à filtrer.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findGeolocalisees(SelectQuery $query): SelectQuery
    {
        return $query
            ->innerJoinWith('Exifs')
            ->contain(['Exifs'])
            ->where([
                'Exifs.gps_lat IS NOT' => null,
                'Exifs.gps_lng IS NOT' => null,
            ]);
    }
}
