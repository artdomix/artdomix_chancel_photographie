<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\TypeConfig;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Config Model
 *
 * @method \App\Model\Entity\Config newEmptyEntity()
 * @method \App\Model\Entity\Config newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Config> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Config get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Config findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Config patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Config> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Config|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Config saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Config>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Config>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Config>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Config> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Config>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Config>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Config>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Config> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ConfigTable extends Table
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

        $this->setTable('config');
        $this->setDisplayField('cle');
        $this->setPrimaryKey('id');

        // Les colonnes ENUM sont typées vers des enums PHP : l'ORM refuse
        // désormais une valeur hors liste, là où une chaîne libre aurait été
        // acceptée puis rejetée silencieusement par MySQL.
        $this->getSchema()->setColumnType('type', EnumType::from(TypeConfig::class));

        $this->addBehavior('Timestamp');
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
            ->scalar('cle')
            ->maxLength('cle', 100)
            ->requirePresence('cle', 'create')
            ->notEmptyString('cle')
            ->add('cle', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('valeur')
            ->allowEmptyString('valeur');

        $validator
            ->scalar('libelle')
            ->maxLength('libelle', 190)
            ->allowEmptyString('libelle');

        // `enum()` plutôt que `scalar()` : la colonne est typée par TypeConfig,
        // et une valeur hors des cas connus doit être refusée à la validation
        // plutôt que de remonter en exception au moment de l'écriture.
        $validator
            ->enum('type', TypeConfig::class)
            ->notEmptyString('type');

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
        $rules->add($rules->isUnique(['cle']), ['errorField' => 'cle']);

        return $rules;
    }
}
