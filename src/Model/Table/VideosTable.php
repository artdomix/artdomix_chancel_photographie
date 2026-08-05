<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Videos Model
 *
 * @property \App\Model\Table\TypevideosTable&\Cake\ORM\Association\BelongsTo $Typevideos
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @method \App\Model\Entity\Video newEmptyEntity()
 * @method \App\Model\Entity\Video newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Video> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Video get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Video findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Video patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Video> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Video|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Video saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Video>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Video>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Video>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Video> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Video>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Video>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Video>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Video> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class VideosTable extends Table
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

        $this->setTable('videos');
        $this->setDisplayField('titre');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', ['field' => 'titre']);

        $this->belongsTo('Typevideos', [
            'foreignKey' => 'typevideo_id',
        ]);
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
            ->integer('typevideo_id')
            ->allowEmptyString('typevideo_id');

        $validator
            ->integer('photo_id')
            ->allowEmptyString('photo_id');

        $validator
            ->scalar('plateforme')
            ->notEmptyString('plateforme');

        $validator
            ->scalar('video_ref')
            ->maxLength('video_ref', 100)
            ->requirePresence('video_ref', 'create')
            ->notEmptyString('video_ref');

        $validator
            ->integer('duree')
            ->allowEmptyString('duree');

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
        $rules->add($rules->existsIn(['typevideo_id'], 'Typevideos'), ['errorField' => 'typevideo_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);

        return $rules;
    }
}
