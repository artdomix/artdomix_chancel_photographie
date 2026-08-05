<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tirages Model
 *
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @property \App\Model\Table\TypetiragesTable&\Cake\ORM\Association\BelongsTo $Typetirages
 * @method \App\Model\Entity\Tirage newEmptyEntity()
 * @method \App\Model\Entity\Tirage newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Tirage> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Tirage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Tirage findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Tirage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Tirage> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Tirage|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Tirage saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Tirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tirage>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tirage> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tirage>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tirage>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tirage> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TiragesTable extends Table
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

        $this->setTable('tirages');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Photos', [
            'foreignKey' => 'photo_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Typetirages', [
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
            ->integer('photo_id')
            ->notEmptyString('photo_id');

        $validator
            ->integer('typetirage_id')
            ->allowEmptyString('typetirage_id');

        $validator
            ->scalar('format')
            ->maxLength('format', 50)
            ->allowEmptyString('format');

        $validator
            ->scalar('papier')
            ->maxLength('papier', 100)
            ->allowEmptyString('papier');

        $validator
            ->decimal('prix')
            ->allowEmptyString('prix');

        $validator
            ->integer('tirage_limite')
            ->allowEmptyString('tirage_limite');

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
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);
        $rules->add($rules->existsIn(['typetirage_id'], 'Typetirages'), ['errorField' => 'typetirage_id']);

        return $rules;
    }
}
