<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Demandes Model
 *
 * @property \App\Model\Table\MessagesTable&\Cake\ORM\Association\HasMany $Messages
 * @method \App\Model\Entity\Demande newEmptyEntity()
 * @method \App\Model\Entity\Demande newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Demande> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Demande get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Demande findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Demande patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Demande> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Demande|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Demande saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Demande>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Demande>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Demande>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Demande> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Demande>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Demande>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Demande>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Demande> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DemandesTable extends Table
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

        $this->setTable('demandes');
        $this->setDisplayField('nom');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', ['field' => 'nom']);

        $this->hasMany('Messages', [
            'foreignKey' => 'demande_id',
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
            ->maxLength('nom', 100)
            ->requirePresence('nom', 'create')
            ->notEmptyString('nom');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 100)
            ->allowEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

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

        return $rules;
    }
}
