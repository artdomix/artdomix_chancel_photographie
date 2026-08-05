<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MoodboardsPhotos Model
 *
 * @property \App\Model\Table\MoodboardsTable&\Cake\ORM\Association\BelongsTo $Moodboards
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @method \App\Model\Entity\MoodboardsPhoto newEmptyEntity()
 * @method \App\Model\Entity\MoodboardsPhoto newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MoodboardsPhoto> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MoodboardsPhoto get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MoodboardsPhoto findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MoodboardsPhoto patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MoodboardsPhoto> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MoodboardsPhoto|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MoodboardsPhoto saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardsPhoto>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardsPhoto> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardsPhoto>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardsPhoto> deleteManyOrFail(iterable $entities, array $options = [])
 */
class MoodboardsPhotosTable extends Table
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

        $this->setTable('moodboards_photos');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Moodboards', [
            'foreignKey' => 'moodboard_id',
            'joinType' => 'INNER',
        ]);
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
            ->integer('moodboard_id')
            ->notEmptyString('moodboard_id');

        $validator
            ->integer('photo_id')
            ->notEmptyString('photo_id');

        $validator
            ->integer('ordre')
            ->notEmptyString('ordre');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->boolean('mise_en_avant')
            ->notEmptyString('mise_en_avant');

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
        $rules->add($rules->isUnique(['moodboard_id', 'photo_id']), ['errorField' => 'moodboard_id', 'message' => __('This combination of moodboard_id and photo_id already exists')]);
        $rules->add($rules->existsIn(['moodboard_id'], 'Moodboards'), ['errorField' => 'moodboard_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);

        return $rules;
    }
}
