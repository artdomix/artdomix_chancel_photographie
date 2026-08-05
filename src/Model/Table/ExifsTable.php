<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Exifs Model
 *
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @method \App\Model\Entity\Exif newEmptyEntity()
 * @method \App\Model\Entity\Exif newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Exif> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Exif get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Exif findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Exif patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Exif> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Exif|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Exif saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Exif>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exif>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Exif>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exif> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Exif>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exif>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Exif>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Exif> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ExifsTable extends Table
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

        $this->setTable('exifs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Photos', [
            'foreignKey' => 'photo_id',
            'joinType' => 'INNER',
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
            ->integer('photo_id')
            ->notEmptyString('photo_id')
            ->add('photo_id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('apn')
            ->maxLength('apn', 150)
            ->allowEmptyString('apn');

        $validator
            ->scalar('objectif')
            ->maxLength('objectif', 150)
            ->allowEmptyString('objectif');

        $validator
            ->scalar('ouverture')
            ->maxLength('ouverture', 20)
            ->allowEmptyString('ouverture');

        $validator
            ->integer('iso')
            ->allowEmptyString('iso');

        $validator
            ->scalar('exposition')
            ->maxLength('exposition', 30)
            ->allowEmptyString('exposition');

        $validator
            ->scalar('focale')
            ->maxLength('focale', 20)
            ->allowEmptyString('focale');

        $validator
            ->scalar('artiste')
            ->maxLength('artiste', 150)
            ->allowEmptyString('artiste');

        $validator
            ->scalar('copyright')
            ->maxLength('copyright', 190)
            ->allowEmptyString('copyright');

        $validator
            ->dateTime('date_capture')
            ->allowEmptyDateTime('date_capture');

        $validator
            ->decimal('gps_lat')
            ->allowEmptyString('gps_lat');

        $validator
            ->decimal('gps_lng')
            ->allowEmptyString('gps_lng');

        $validator
            ->decimal('gps_altitude')
            ->allowEmptyString('gps_altitude');

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
        $rules->add($rules->isUnique(['photo_id']), ['errorField' => 'photo_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);

        return $rules;
    }
}
