<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Galeries Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Clients
 * @property \App\Model\Table\GalerieCommentairesTable&\Cake\ORM\Association\HasMany $GalerieCommentaires
 * @property \App\Model\Table\GalerieFavorisTable&\Cake\ORM\Association\HasMany $GalerieFavoris
 * @property \App\Model\Table\PhotosTable&\Cake\ORM\Association\BelongsToMany $Photos
 * @method \App\Model\Entity\Galerie newEmptyEntity()
 * @method \App\Model\Entity\Galerie newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Galerie> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Galerie get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Galerie findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Galerie patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Galerie> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Galerie|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Galerie saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Galerie>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Galerie>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Galerie>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Galerie> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Galerie>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Galerie>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Galerie>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Galerie> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class GaleriesTable extends Table
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

        $this->setTable('galeries');
        $this->setDisplayField('nom');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Partageable');
        $this->addBehavior('Sluggable', ['field' => 'nom']);

        $this->belongsTo('Clients', [
            'foreignKey' => 'client_id',
            'className' => 'Users',
        ]);
        $this->hasMany('GalerieCommentaires', [
            'foreignKey' => 'galerie_id',
        ]);
        $this->hasMany('GalerieFavoris', [
            'foreignKey' => 'galerie_id',
        ]);
        $this->belongsToMany('Photos', [
            'foreignKey' => 'galerie_id',
            'targetForeignKey' => 'photo_id',
            'joinTable' => 'galeries_photos',
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
            ->scalar('nom')
            ->maxLength('nom', 190)
            ->requirePresence('nom', 'create')
            ->notEmptyString('nom');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 190)
            ->allowEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->integer('client_id')
            ->allowEmptyString('client_id');

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
            ->boolean('telechargement_actif')
            ->notEmptyString('telechargement_actif');

        $validator
            ->integer('quota_favoris')
            ->notEmptyString('quota_favoris');

        $validator
            ->date('date_livraison')
            ->allowEmptyDate('date_livraison');

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
        $rules->add($rules->isUnique(['share_token']), ['errorField' => 'share_token']);
        $rules->add($rules->isUnique(['slug']), ['errorField' => 'slug']);
        $rules->add($rules->existsIn(['client_id'], 'Clients'), ['errorField' => 'client_id']);

        return $rules;
    }
}
