<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use RuntimeException;

/**
 * Rend les assets construits par Vite à partir de webroot/build/.vite/manifest.json.
 *
 * Pourquoi un helper maison plutôt qu'un plugin : l'hébergement est mutualisé, le
 * build est fait en local et committé, et on n'a donc besoin que de lire un
 * manifeste — une cinquantaine de lignes, sans dépendance supplémentaire à suivre.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ViteHelper extends Helper
{
    /**
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'manifestPath' => WWW_ROOT . 'build' . DS . '.vite' . DS . 'manifest.json',
        'baseUrl' => '/build/',
    ];

    /**
     * Manifeste décodé, mémorisé pour la durée de la requête.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $manifest = null;

    /**
     * Rend les balises `<link>` et `<script>` d'une entrée Vite.
     *
     * @param string $entree Chemin de l'entrée, ex. « resources/js/app.js ».
     * @return string
     */
    public function asset(string $entree): string
    {
        $manifest = $this->lireManifeste();

        if (!isset($manifest[$entree])) {
            if (Configure::read('debug')) {
                throw new RuntimeException(sprintf(
                    'Entrée Vite « %s » absente du manifeste. As-tu lancé `npm run build` ?',
                    $entree,
                ));
            }

            return '';
        }

        $base = $this->getConfig('baseUrl');
        $sortie = '';

        // Le CSS n'est pas forcément porté par l'entrée elle-même : quand plusieurs
        // entrées partagent une feuille de style, Vite la rattache au chunk commun.
        // Il faut donc descendre récursivement le graphe des imports, sinon la page
        // sort sans aucun style.
        foreach ($this->collecterCss($entree, $manifest) as $fichierCss) {
            $sortie .= $this->Html->css($base . $fichierCss);
        }

        $sortie .= $this->Html->script($base . $manifest[$entree]['file'], ['type' => 'module']);

        return $sortie;
    }

    /**
     * Collecte les feuilles de style d'une entrée et de tous ses imports.
     *
     * @param string $nom Clé du chunk dans le manifeste.
     * @param array<string, mixed> $manifest Manifeste décodé.
     * @param array<string, bool> $vus Chunks déjà traversés (garde anti-cycle).
     * @return array<string>
     */
    protected function collecterCss(string $nom, array $manifest, array &$vus = []): array
    {
        if (isset($vus[$nom]) || !isset($manifest[$nom])) {
            return [];
        }

        $vus[$nom] = true;
        $chunk = $manifest[$nom];
        $css = $chunk['css'] ?? [];

        foreach ($chunk['imports'] ?? [] as $import) {
            $css = array_merge($css, $this->collecterCss($import, $manifest, $vus));
        }

        return array_unique($css);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lireManifeste(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $chemin = $this->getConfig('manifestPath');

        if (!is_file($chemin)) {
            if (Configure::read('debug')) {
                throw new RuntimeException(sprintf(
                    'Manifeste Vite introuvable (%s). Lance `npm run build`.',
                    $chemin,
                ));
            }

            return $this->manifest = [];
        }

        $contenu = json_decode((string)file_get_contents($chemin), true);

        return $this->manifest = is_array($contenu) ? $contenu : [];
    }
}
