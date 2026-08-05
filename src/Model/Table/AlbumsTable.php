<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\VisibiliteAlbum;
use Cake\Database\Type\EnumType;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Albums Model
 *
 * @property \App\Model\Table\AlbumsTable&\Cake\ORM\Association\BelongsTo $ParentAlbums
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $CoverPhotos
 * @property \App\Model\Table\AlbumsTable&\Cake\ORM\Association\HasMany $ChildAlbums
 * @property \App\Model\Table\ShootingsTable&\Cake\ORM\Association\HasMany $Shootings
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsToMany $Photos
 * @method \App\Model\Entity\Album newEmptyEntity()
 * @method \App\Model\Entity\Album newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Album> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Album get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Album findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Album patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Album> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Album|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Album saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Album>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Album>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Album>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Album> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Album>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Album>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Album>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Album> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Cake\ORM\Behavior\TreeBehavior
 */
class AlbumsTable extends Table
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

        $this->setTable('albums');
        $this->setDisplayField('nom');
        $this->setPrimaryKey('id');

        // Les colonnes ENUM sont typées vers des enums PHP : l'ORM refuse
        // désormais une valeur hors liste, là où une chaîne libre aurait été
        // acceptée puis rejetée silencieusement par MySQL.
        $this->getSchema()->setColumnType('visibilite', EnumType::from(VisibiliteAlbum::class));

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', ['field' => 'nom']);
        $this->addBehavior('Tree');

        $this->belongsTo('ParentAlbums', [
            'className' => 'Albums',
            'foreignKey' => 'parent_id',
        ]);
        $this->belongsTo('CoverPhotos', [
            'foreignKey' => 'cover_photo_id',
            'className' => 'Photos',
        ]);
        $this->hasMany('ChildAlbums', [
            'className' => 'Albums',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('Shootings', [
            'foreignKey' => 'album_id',
        ]);
        $this->belongsToMany('Photos', [
            'foreignKey' => 'album_id',
            'targetForeignKey' => 'photo_id',
            'joinTable' => 'albums_photos',
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
            ->scalar('nom')
            ->maxLength('nom', 190)
            ->requirePresence('nom', 'create')
            ->notEmptyString('nom');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 190)
            ->allowEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->integer('parent_id')
            ->allowEmptyString('parent_id');

        $validator
            ->integer('cover_photo_id')
            ->allowEmptyString('cover_photo_id');

        // `enum()` plutôt que `scalar()` : la colonne est typée par VisibiliteAlbum,
        // et une valeur hors des cas connus doit être refusée à la validation
        // plutôt que de remonter en exception au moment de l'écriture.
        $validator
            ->enum('visibilite', VisibiliteAlbum::class)
            ->notEmptyString('visibilite');

        $validator
            ->integer('ordre')
            ->notEmptyString('ordre');

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
        $rules->add($rules->existsIn(['parent_id'], 'ParentAlbums'), ['errorField' => 'parent_id']);
        $rules->add($rules->existsIn(['cover_photo_id'], 'CoverPhotos'), ['errorField' => 'cover_photo_id']);

        return $rules;
    }

    /**
     * Albums visibles du public.
     *
     * Le portfolio public ne doit jamais laisser filtrer un album marqué privé,
     * y compris via un lien direct : le filtre est ici, dans le modèle, plutôt
     * que répété dans chaque contrôleur où on finirait par l'oublier.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Requête à filtrer.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findPublics(SelectQuery $query): SelectQuery
    {
        return $query->where([
            $this->aliasField('actif') => true,
            $this->aliasField('visibilite') => 'public',
        ]);
    }

    /**
     * Albums racines, dans l'ordre de l'arbre.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Requête à filtrer.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findRacines(SelectQuery $query): SelectQuery
    {
        return $query
            ->where([$this->aliasField('parent_id') . ' IS' => null])
            ->orderBy([$this->aliasField('lft') => 'ASC']);
    }
}
