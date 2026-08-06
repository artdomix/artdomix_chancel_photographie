<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Helper;

/**
 * Éléments du pied de page communs à toutes les pages du front.
 *
 * Le layout a besoin de la liste des pages éditoriales, qu'aucun contrôleur ne
 * lui fournit — et il y en a une dizaine. Plutôt que de charger chacun d'eux
 * d'une requête sans rapport avec son sujet, elle est faite ici, une fois.
 */
class PiedHelper extends Helper
{
    use LocatorAwareTrait;

    /**
     * Pages actives, mémorisées pour la durée de la requête.
     *
     * @var array<string, string>|null
     */
    protected ?array $pages = null;

    /**
     * Pages éditoriales publiées : slug => titre.
     *
     * @return array<string, string>
     */
    public function pages(): array
    {
        if ($this->pages !== null) {
            return $this->pages;
        }

        return $this->pages = $this->fetchTable('Pages')->find('list', keyField: 'slug', valueField: 'titre')
            ->where(['Pages.actif' => true])
            ->orderBy(['Pages.titre' => 'ASC'])
            ->toArray();
    }
}
