<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Modeles Model
 *
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @property \App\Model\Table\ShootingsTable&\Cake\ORM\Association\HasMany $Shootings
 * @method \App\Model\Entity\Modele newEmptyEntity()
 * @method \App\Model\Entity\Modele newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Modele> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Modele get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Modele findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Modele patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Modele> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Modele|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Modele saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Modele>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Modele>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Modele>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Modele> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Modele>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Modele>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Modele>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Modele> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ModelesTable extends Table
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

        $this->setTable('modeles');
        $this->setDisplayField('nom');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', ['field' => 'nom']);

        $this->belongsTo('Photos', [
            'foreignKey' => 'photo_id',
        ]);
        $this->hasMany('Shootings', [
            'foreignKey' => 'modele_id',
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
            ->maxLength('nom', 150)
            ->requirePresence('nom', 'create')
            ->notEmptyString('nom');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 150)
            ->allowEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->integer('photo_id')
            ->allowEmptyString('photo_id');

        $validator
            ->scalar('instagram')
            ->maxLength('instagram', 100)
            ->allowEmptyString('instagram');

        $validator
            ->scalar('site')
            ->maxLength('site', 255)
            ->allowEmptyString('site');

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
