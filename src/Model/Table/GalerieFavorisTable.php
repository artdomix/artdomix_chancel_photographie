<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * GalerieFavoris Model
 *
 * @property \App\Model\Table\GaleriesTable&\Cake\ORM\Association\BelongsTo $Galeries
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\GalerieFavori newEmptyEntity()
 * @method \App\Model\Entity\GalerieFavori newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\GalerieFavori> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\GalerieFavori get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\GalerieFavori findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\GalerieFavori patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\GalerieFavori> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\GalerieFavori|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\GalerieFavori saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieFavori>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieFavori>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieFavori>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieFavori> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieFavori>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieFavori>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieFavori>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieFavori> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class GalerieFavorisTable extends Table
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

        $this->setTable('galerie_favoris');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Galeries', [
            'foreignKey' => 'galerie_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Photos', [
            'foreignKey' => 'photo_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->integer('galerie_id')
            ->notEmptyString('galerie_id');

        $validator
            ->integer('photo_id')
            ->notEmptyString('photo_id');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->scalar('session_key')
            ->maxLength('session_key', 64)
            ->allowEmptyString('session_key');

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
        $rules->add($rules->isUnique(['galerie_id', 'photo_id', 'user_id'], ['allowMultipleNulls' => true]), ['errorField' => 'galerie_id', 'message' => __('This combination of galerie_id, photo_id and user_id already exists')]);
        $rules->add($rules->existsIn(['galerie_id'], 'Galeries'), ['errorField' => 'galerie_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
