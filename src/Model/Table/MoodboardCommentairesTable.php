<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MoodboardCommentaires Model
 *
 * @property \App\Model\Table\MoodboardsTable&\Cake\ORM\Association\BelongsTo $Moodboards
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @method \App\Model\Entity\MoodboardCommentaire newEmptyEntity()
 * @method \App\Model\Entity\MoodboardCommentaire newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MoodboardCommentaire> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MoodboardCommentaire get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MoodboardCommentaire findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MoodboardCommentaire patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MoodboardCommentaire> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MoodboardCommentaire|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MoodboardCommentaire saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardCommentaire>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardCommentaire> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardCommentaire>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MoodboardCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MoodboardCommentaire> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MoodboardCommentairesTable extends Table
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

        $this->setTable('moodboard_commentaires');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Moodboards', [
            'foreignKey' => 'moodboard_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->integer('moodboard_id')
            ->notEmptyString('moodboard_id');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->integer('photo_id')
            ->allowEmptyString('photo_id');

        $validator
            ->scalar('auteur')
            ->maxLength('auteur', 100)
            ->allowEmptyString('auteur');

        $validator
            ->scalar('contenu')
            ->requirePresence('contenu', 'create')
            ->notEmptyString('contenu');

        $validator
            ->boolean('valide')
            ->notEmptyString('valide');

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
        $rules->add($rules->existsIn(['moodboard_id'], 'Moodboards'), ['errorField' => 'moodboard_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);

        return $rules;
    }
}
