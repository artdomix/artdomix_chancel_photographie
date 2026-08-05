<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Utility\Security;

/**
 * Ressources accessibles par un lien de partage : moodboards et galeries client.
 *
 * Attribue à la création un jeton opaque tiré au sort. C'est lui qui figure dans
 * l'URL publique, jamais l'identifiant numérique : une URL en `/m/1` inviterait à
 * essayer `/m/2`, et un moodboard privé n'est pas censé être devinable.
 *
 * 32 octets aléatoires encodés en hexadécimal, soit 64 caractères — la taille de
 * la colonne. Assez large pour rendre l'énumération sans objet.
 */
class PartageableBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'tokenField' => 'share_token',
        'length' => 32,
    ];

    /**
     * @param \Cake\Event\EventInterface $event Événement déclencheur.
     * @param \Cake\Datasource\EntityInterface $entity Entité sauvegardée.
     * @param \ArrayObject $options Options de sauvegarde.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $champ = (string)$this->getConfig('tokenField');

        // Jamais régénéré : le lien a pu être envoyé au client, le changer le
        // casserait sans prévenir. La révocation est une action explicite.
        if (!empty($entity->get($champ))) {
            return;
        }

        $entity->set($champ, $this->genererToken());
    }

    /**
     * Remplace le jeton, invalidant tous les liens déjà diffusés.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entité à révoquer.
     * @return \Cake\Datasource\EntityInterface|false
     */
    public function revoquerLien(EntityInterface $entity): EntityInterface|false
    {
        $entity->set((string)$this->getConfig('tokenField'), $this->genererToken());

        return $this->_table->save($entity);
    }

    /**
     * @return string
     */
    protected function genererToken(): string
    {
        return bin2hex(Security::randomBytes((int)$this->getConfig('length')));
    }
}
