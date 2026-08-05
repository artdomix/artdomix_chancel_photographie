<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Moodboards Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Destinataires
 * @property \App\Model\Table\MoodboardCommentairesTable&\Cake\ORM\Association\HasMany $MoodboardCommentaires
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsToMany $Photos
 * @method \App\Model\Entity\Moodboard newEmptyEntity()
 * @method \App\Model\Entity\Moodboard newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Moodboard> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Moodboard get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Moodboard findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Moodboard patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Moodboard> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Moodboard|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Moodboard saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Moodboard>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Moodboard>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Moodboard>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Moodboard> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Moodboard>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Moodboard>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Moodboard>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Moodboard> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MoodboardsTable extends Table
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

        $this->setTable('moodboards');
        $this->setDisplayField('titre');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Partageable');
        $this->addBehavior('Sluggable', ['field' => 'titre']);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
        $this->belongsTo('Destinataires', [
            'foreignKey' => 'destinataire_id',
            'className' => 'Users',
        ]);
        $this->hasMany('MoodboardCommentaires', [
            'foreignKey' => 'moodboard_id',
        ]);
        $this->belongsToMany('Photos', [
            'foreignKey' => 'moodboard_id',
            'targetForeignKey' => 'photo_id',
            'joinTable' => 'moodboards_photos',
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
            ->scalar('theme')
            ->notEmptyString('theme');

        $validator
            ->scalar('visibilite')
            ->notEmptyString('visibilite');

        $validator
            ->scalar('share_token')
            ->maxLength('share_token', 64)
            ->allowEmptyString('share_token')
            ->add('share_token', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password_hash')
            ->maxLength('password_hash', 255)
            ->allowEmptyString('password_hash');

        $validator
            ->dateTime('expires_at')
            ->allowEmptyDateTime('expires_at');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->integer('destinataire_id')
            ->allowEmptyString('destinataire_id');

        $validator
            ->boolean('commentaires_actifs')
            ->notEmptyString('commentaires_actifs');

        $validator
            ->integer('vues')
            ->notEmptyString('vues');

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
        $rules->add($rules->isUnique(['share_token']), ['errorField' => 'share_token']);
        $rules->add($rules->isUnique(['slug']), ['errorField' => 'slug']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['destinataire_id'], 'Destinataires'), ['errorField' => 'destinataire_id']);

        return $rules;
    }
}
