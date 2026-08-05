<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PhotosTags Model
 *
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @property \App\Model\Table\TagsTable&\Cake\ORM\Association\BelongsTo $Tags
 * @method \App\Model\Entity\PhotosTag newEmptyEntity()
 * @method \App\Model\Entity\PhotosTag newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\PhotosTag> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PhotosTag get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PhotosTag findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\PhotosTag patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\PhotosTag> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\PhotosTag|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\PhotosTag saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\PhotosTag>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PhotosTag>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PhotosTag>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PhotosTag> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PhotosTag>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PhotosTag>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PhotosTag>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PhotosTag> deleteManyOrFail(iterable $entities, array $options = [])
 */
class PhotosTagsTable extends Table
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

        $this->setTable('photos_tags');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Photos', [
            'foreignKey' => 'photo_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Tags', [
            'foreignKey' => 'tag_id',
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
            ->notEmptyString('photo_id');

        $validator
            ->integer('tag_id')
            ->notEmptyString('tag_id');

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
        $rules->add($rules->isUnique(['photo_id', 'tag_id']), ['errorField' => 'photo_id', 'message' => __('This combination of photo_id and tag_id already exists')]);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);
        $rules->add($rules->existsIn(['tag_id'], 'Tags'), ['errorField' => 'tag_id']);

        return $rules;
    }
}
