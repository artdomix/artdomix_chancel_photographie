<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \App\Model\Table\CommentairesTable&\Cake\ORM\Association\HasMany $Commentaires
 * @property \App\Model\Table\GalerieCommentairesTable&\Cake\ORM\Association\HasMany $GalerieCommentaires
 * @property \App\Model\Table\GalerieFavorisTable&\Cake\ORM\Association\HasMany $GalerieFavoris
 * @property \App\Model\Table\MoodboardCommentairesTable&\Cake\ORM\Association\HasMany $MoodboardCommentaires
 * @property \App\Model\Table\MoodboardsTable&\Cake\ORM\Association\HasMany $Moodboards
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\User> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\User> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
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

        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Commentaires', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('GalerieCommentaires', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('GalerieFavoris', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('MoodboardCommentaires', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('Moodboards', [
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
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator
            ->scalar('role')
            ->notEmptyString('role');

        $validator
            ->scalar('prenom')
            ->maxLength('prenom', 100)
            ->allowEmptyString('prenom');

        $validator
            ->scalar('nom')
            ->maxLength('nom', 100)
            ->allowEmptyString('nom');

        $validator
            ->scalar('societe')
            ->maxLength('societe', 150)
            ->allowEmptyString('societe');

        $validator
            ->scalar('telephone')
            ->maxLength('telephone', 30)
            ->allowEmptyString('telephone');

        $validator
            ->boolean('actif')
            ->notEmptyString('actif');

        $validator
            ->scalar('token')
            ->maxLength('token', 64)
            ->allowEmptyString('token');

        $validator
            ->dateTime('token_expire')
            ->allowEmptyDateTime('token_expire');

        $validator
            ->dateTime('derniere_connexion')
            ->allowEmptyDateTime('derniere_connexion');

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
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        return $rules;
    }

    /**
     * Comptes actifs uniquement.
     *
     * Utilisé comme resolver de l'identifiant : désactiver un compte doit
     * suffire à interdire la connexion, sans avoir à changer le mot de passe.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Requête à filtrer.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActifs(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('actif') => true]);
    }

    /**
     * Règles appliquées au choix d'un mot de passe.
     *
     * Séparées de la validation par défaut : celle-ci s'applique aussi aux
     * modifications de profil qui ne touchent pas au mot de passe.
     *
     * Longueur minimale de 12 caractères plutôt que 8, sans exigence de
     * caractères spéciaux : c'est la recommandation actuelle de l'ANSSI et du
     * NIST — une phrase longue résiste mieux qu'un « P@ss1! » court.
     *
     * @param \Cake\Validation\Validator $validator Validateur à configurer.
     * @return \Cake\Validation\Validator
     */
    public function validationMotDePasse(Validator $validator): Validator
    {
        $validator
            ->scalar('password')
            ->requirePresence('password')
            ->notEmptyString('password', __('Le mot de passe est obligatoire.'))
            ->minLength('password', 12, __('12 caractères minimum.'))
            ->maxLength('password', 4096);

        return $validator;
    }
}
