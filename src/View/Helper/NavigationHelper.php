<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Helper;

/**
 * Ce que le site public propose comme chemins : menu, pied de page, compte.
 *
 * Décrire la navigation ici plutôt que dans le gabarit sert deux choses. Le
 * repérage de la page courante — savoir que `/portfolio/voyage/japon` relève de
 * « Portfolio » — demande une logique qui n'a pas sa place au milieu du HTML. Et
 * la liste des rubriques devient vérifiable : un test peut confronter chaque
 * entrée du menu aux routes réellement servies, ce qu'aucune relecture de
 * gabarit ne garantit.
 */
class NavigationHelper extends Helper
{
    use LocatorAwareTrait;

    /**
     * Rubriques publiques du menu : chemin => libellé.
     *
     * L'ordre est celui de l'affichage. Le portfolio vient en premier parce que
     * c'est ce qu'un visiteur de site de photographe cherche ; le contact en
     * dernier, parce qu'on y va après avoir vu le reste.
     *
     * @var array<string, string>
     */
    protected const PRINCIPAL = [
        '/portfolio' => 'Portfolio',
        '/tirages' => 'Tirages',
        '/livres' => 'Livres',
        '/expositions' => 'Expositions',
        '/videos' => 'Vidéos',
        '/carte' => 'Carte',
        '/recherche' => 'Recherche',
        '/blog' => 'Blog',
        '/contact' => 'Contact',
    ];

    /**
     * Chemins qui appartiennent à une rubrique sans en porter le préfixe.
     *
     * Une photo isolée ou un tag relèvent du portfolio : sans cette table, ouvrir
     * une photo éteindrait tout le menu, et le visiteur ne saurait plus où il est.
     *
     * @var array<string, array<string>>
     */
    protected const RATTACHEMENTS = [
        '/portfolio' => ['/photo', '/tag'],
    ];

    /**
     * Pages éditoriales actives, mémorisées pour la durée de la requête.
     *
     * @var array<string, string>|null
     */
    protected ?array $pages = null;

    /**
     * Entrées du menu principal : chemin, libellé, et si c'est la page courante.
     *
     * @return list<array{chemin: string, libelle: string, actif: bool}>
     */
    public function principal(): array
    {
        $entrees = [];

        foreach (self::PRINCIPAL as $chemin => $libelle) {
            $entrees[] = [
                'chemin' => $chemin,
                'libelle' => $libelle,
                'actif' => $this->estActif($chemin),
            ];
        }

        return $entrees;
    }

    /**
     * La rubrique donnée est-elle celle de la page affichée ?
     *
     * @param string $chemin Chemin de la rubrique.
     * @return bool
     */
    public function estActif(string $chemin): bool
    {
        $courant = '/' . trim($this->getView()->getRequest()->getPath(), '/');

        foreach ([$chemin, ...(self::RATTACHEMENTS[$chemin] ?? [])] as $prefixe) {
            if ($courant === $prefixe || str_starts_with($courant, $prefixe . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Entrée « compte » de l'en-tête, adaptée au visiteur.
     *
     * Sans elle, un client possédant un compte n'avait aucun moyen d'atteindre
     * son espace depuis le site : il fallait connaître l'URL par cœur.
     *
     * @return list<array{chemin: string, libelle: string}>
     */
    public function compte(): array
    {
        $identite = $this->getView()->getRequest()->getAttribute('identity');

        if ($identite === null) {
            return [['chemin' => '/connexion', 'libelle' => 'Connexion']];
        }

        $compte = $identite->getOriginalData();

        return [
            [
                // L'administrateur retombe sur son back-office, le client sur ses
                // livraisons : le même lien ne mène pas au même endroit.
                'chemin' => $compte->estAdmin() ? '/admin' : '/membre',
                'libelle' => $compte->estAdmin() ? 'Administration' : 'Mon espace',
            ],
            ['chemin' => '/deconnexion', 'libelle' => 'Déconnexion'],
        ];
    }

    /**
     * Pages éditoriales publiées, listées en pied de page : slug => titre.
     *
     * La requête est faite ici plutôt que dans chacun des contrôleurs du front,
     * qui n'ont aucune raison de s'en charger.
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
