<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Commentaires Model
 *
 * @property \App\Model\Table\ArticlesTable&\Cake\ORM\Association\BelongsTo $Articles
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\Commentaire newEmptyEntity()
 * @method \App\Model\Entity\Commentaire newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Commentaire> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Commentaire get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Commentaire findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Commentaire patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Commentaire> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Commentaire|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Commentaire saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Commentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Commentaire>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Commentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Commentaire> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Commentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Commentaire>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Commentaire>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Commentaire> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CommentairesTable extends Table
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

        $this->setTable('commentaires');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Articles', [
            'foreignKey' => 'article_id',
            'joinType' => 'INNER',
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
            ->integer('article_id')
            ->notEmptyString('article_id');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->scalar('auteur')
            ->maxLength('auteur', 100)
            ->allowEmptyString('auteur');

        $validator
            ->email('email')
            ->allowEmptyString('email');

        $validator
            ->scalar('contenu')
            ->requirePresence('contenu', 'create')
            ->notEmptyString('contenu');

        $validator
            ->boolean('valide')
            ->notEmptyString('valide');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 45)
            ->allowEmptyString('ip');

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
        $rules->add($rules->existsIn(['article_id'], 'Articles'), ['errorField' => 'article_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
