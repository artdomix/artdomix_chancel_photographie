<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Expositions Model
 *
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @method \App\Model\Entity\Exposition newEmptyEntity()
 * @method \App\Model\Entity\Exposition newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Exposition> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Exposition get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Exposition findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Exposition patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Exposition> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Exposition|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Exposition saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Exposition>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exposition>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Exposition>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exposition> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Exposition>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exposition>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Exposition>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exposition> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ExpositionsTable extends Table
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

        $this->setTable('expositions');
        $this->setDisplayField('titre');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', ['field' => 'titre']);

        $this->belongsTo('Photos', [
            'foreignKey' => 'photo_id',
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
            ->scalar('titre')
            ->maxLength('titre', 190)
            ->requirePresence('titre', 'create')
            ->notEmptyString('titre');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 190)
            ->allowEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('lieu')
            ->maxLength('lieu', 190)
            ->allowEmptyString('lieu');

        $validator
            ->integer('photo_id')
            ->allowEmptyString('photo_id');

        $validator
            ->date('date_debut')
            ->allowEmptyDate('date_debut');

        $validator
            ->date('date_fin')
            ->allowEmptyDate('date_fin');

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
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);

        return $rules;
    }
}
