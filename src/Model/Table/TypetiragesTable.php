<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Typetirages Model
 *
 * @property \App\Model\Table\TiragesTable&\Cake\ORM\Association\HasMany $Tirages
 * @method \App\Model\Entity\Typetirage newEmptyEntity()
 * @method \App\Model\Entity\Typetirage newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Typetirage> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Typetirage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Typetirage findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Typetirage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Typetirage> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Typetirage|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Typetirage saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Typetirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Typetirage>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Typetirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Typetirage> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Typetirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Typetirage>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Typetirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Typetirage> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TypetiragesTable extends Table
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

        $this->setTable('typetirages');
        $this->setDisplayField('nom');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', ['field' => 'nom']);

        $this->hasMany('Tirages', [
            'foreignKey' => 'typetirage_id',
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
