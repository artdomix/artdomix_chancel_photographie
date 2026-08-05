<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * GaleriesPhotos Model
 *
 * @property \App\Model\Table\GaleriesTable&\Cake\ORM\Association\BelongsTo $Galeries
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @method \App\Model\Entity\GaleriesPhoto newEmptyEntity()
 * @method \App\Model\Entity\GaleriesPhoto newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\GaleriesPhoto> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\GaleriesPhoto get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\GaleriesPhoto findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\GaleriesPhoto patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\GaleriesPhoto> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\GaleriesPhoto|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\GaleriesPhoto saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\GaleriesPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GaleriesPhoto>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GaleriesPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GaleriesPhoto> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GaleriesPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GaleriesPhoto>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GaleriesPhoto>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GaleriesPhoto> deleteManyOrFail(iterable $entities, array $options = [])
 */
class GaleriesPhotosTable extends Table
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

        $this->setTable('galeries_photos');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Galeries', [
            'foreignKey' => 'galerie_id',
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
            ->integer('galerie_id')
            ->notEmptyString('galerie_id');

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
        $rules->add($rules->isUnique(['galerie_id', 'photo_id']), ['errorField' => 'galerie_id', 'message' => __('This combination of galerie_id and photo_id already exists')]);
        $rules->add($rules->existsIn(['galerie_id'], 'Galeries'), ['errorField' => 'galerie_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);

        return $rules;
    }
}
