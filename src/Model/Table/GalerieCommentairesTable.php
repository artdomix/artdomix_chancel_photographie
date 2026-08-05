<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * GalerieCommentaires Model
 *
 * @property \App\Model\Table\GaleriesTable&\Cake\ORM\Association\BelongsTo $Galeries
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsTo $Photos
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\GalerieCommentaire newEmptyEntity()
 * @method \App\Model\Entity\GalerieCommentaire newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\GalerieCommentaire> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\GalerieCommentaire get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\GalerieCommentaire findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\GalerieCommentaire patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\GalerieCommentaire> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\GalerieCommentaire|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\GalerieCommentaire saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieCommentaire>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieCommentaire> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieCommentaire>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\GalerieCommentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GalerieCommentaire> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class GalerieCommentairesTable extends Table
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

        $this->setTable('galerie_commentaires');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Galeries', [
            'foreignKey' => 'galerie_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Photos', [
            'foreignKey' => 'photo_id',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->allowEmptyString('photo_id');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->scalar('auteur')
            ->maxLength('auteur', 100)
            ->allowEmptyString('auteur');

        $validator
            ->scalar('contenu')
            ->requirePresence('contenu', 'create')
            ->notEmptyString('contenu');

        $validator
            ->boolean('lu')
            ->notEmptyString('lu');

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
        $rules->add($rules->existsIn(['galerie_id'], 'Galeries'), ['errorField' => 'galerie_id']);
        $rules->add($rules->existsIn(['photo_id'], 'Photos'), ['errorField' => 'photo_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
