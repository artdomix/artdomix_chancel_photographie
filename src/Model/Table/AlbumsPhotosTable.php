<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AlbumsPhotos Model
 *
 * @property \App\Model\Table\AlbumsTable&\Cake\ORM\Association\BelongsTo $Albums
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @method \App\Model\Entity\AlbumsPhoto newEmptyEntity()
 * @method \App\Model\Entity\AlbumsPhoto newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\AlbumsPhoto> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AlbumsPhoto get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AlbumsPhoto findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\AlbumsPhoto patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\AlbumsPhoto> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AlbumsPhoto|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\AlbumsPhoto saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\AlbumsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlbumsPhoto>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlbumsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlbumsPhoto> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlbumsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlbumsPhoto>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlbumsPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlbumsPhoto> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AlbumsPhotosTable extends Table
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

        $this->setTable('albums_photos');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Albums', [
            'foreignKey' => 'album_id',
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
            ->integer('album_id')
            ->notEmptyString('album_id');

        $validator
            ->integer('photo_id')
            ->notEmptyString('photo_id');

        $validator
            ->integer('ordre')
            ->notEmptyString('ordre');

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
        $rules->add($rules->isUnique(['album_id', 'photo_id']), ['errorField' => 'album_id', 'message' => __('This combination of album_id and photo_id already exists')]);
        $rules->add($rules->existsIn(['album_id'], 'Albums'), ['errorField' => 'album_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);

        return $rules;
    }
}
