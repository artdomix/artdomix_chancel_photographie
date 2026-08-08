<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

/**
 * Charge les bibliothèques front depuis un CDN, et les fichiers du projet depuis
 * `webroot/`.
 *
 * Remplace le `ViteHelper` : le projet n'a plus aucune étape de build, donc plus
 * besoin de Node — ni sur le poste de développement, ni en CI, ni sur
 * l'hébergement mutualisé qui n'en dispose pas. Le prix est une dépendance à un
 * CDN externe, dont les conséquences sont détaillées dans le README.
 *
 * Les versions sont **épinglées**. Une version flottante (`gsap@3` au lieu de
 * `gsap@3.15.0`) ferait changer le code exécuté par le site sans qu'aucun commit
 * ne l'indique, et rendrait tout contrôle d'intégrité impossible.
 */
class AssetsHelper extends Helper
{
    /**
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * Racine du CDN. Regroupée ici pour qu'un changement de fournisseur ne se
     * fasse qu'à un seul endroit.
     */
    protected const CDN = 'https://cdn.jsdelivr.net/npm/';

    /**
     * Bibliothèques tierces, version épinglée, chemin relatif à self::CDN.
     *
     * @var array<string, string>
     */
    protected const LIBS = [
        'tailwind' => '@tailwindcss/browser@4.3.3/dist/index.global.js',
        'htmx' => 'htmx.org@2.0.10/dist/htmx.min.js',
        'gsap' => 'gsap@3.15.0/dist/gsap.min.js',
        'scrolltrigger' => 'gsap@3.15.0/dist/ScrollTrigger.min.js',
        'flip' => 'gsap@3.15.0/dist/Flip.min.js',
        'leaflet' => 'leaflet@1.9.4/dist/leaflet.js',
        'leaflet_css' => 'leaflet@1.9.4/dist/leaflet.css',
    ];

    /**
     * Polices, servies par le même CDN que le reste.
     *
     * Fontsource plutôt que Google Fonts : la CNIL considère qu'appeler
     * `fonts.googleapis.com` transmet l'adresse IP du visiteur à un tiers sans
     * base légale. Sous-ensemble latin uniquement — le site est en français,
     * embarquer le cyrillique et le grec tripleraît le poids pour rien.
     *
     * @var array<string>
     */
    protected const POLICES = [
        '@fontsource/oswald@5.3.0/latin-300.css',
        '@fontsource/oswald@5.3.0/latin-400.css',
        '@fontsource/oswald@5.3.0/latin-500.css',
        '@fontsource/open-sans@5.3.0/latin-300.css',
        '@fontsource/open-sans@5.3.0/latin-400.css',
        '@fontsource/open-sans@5.3.0/latin-600.css',
    ];

    /**
     * Source Tailwind du site, injectée telle quelle dans la page.
     */
    protected const THEME = WWW_ROOT . 'css' . DS . 'chancel-theme.css';

    /**
     * Contenu du thème, lu une fois par requête.
     */
    protected static ?string $theme = null;

    /**
     * Balises communes à toutes les pages : repli, polices, Tailwind et thème.
     *
     * @return string
     */
    public function base(): string
    {
        $sortie = '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>' . "\n";

        // Le repli passe en premier : il doit s'appliquer pendant que Tailwind se
        // charge et se compile, et rester seul en cas d'échec du CDN.
        $sortie .= $this->Html->css('/css/chancel-repli.css');

        foreach (self::POLICES as $police) {
            $sortie .= $this->Html->css(self::CDN . $police);
        }

        // Tailwind doit venir AVANT le bloc de thème : c'est lui qui interprète
        // `<style type="text/tailwindcss">`, et il relit le document à chaque
        // ajout de nœud, donc l'ordre d'apparition n'a pas d'autre contrainte.
        $sortie .= $this->script('tailwind');
        $sortie .= $this->theme();

        return $sortie;
    }

    /**
     * Bibliothèques et script du front public.
     *
     * @return string
     */
    public function front(): string
    {
        return $this->script('htmx')
            . $this->script('gsap')
            . $this->script('scrolltrigger')
            . $this->script('flip')
            . $this->Html->script('/js/chancel.js', ['defer' => true]);
    }

    /**
     * Bibliothèques et script du back-office.
     *
     * Ni GSAP ni ses plugins : l'administration n'anime rien, et charger 100 Ko
     * de bibliothèque d'animation sur un formulaire n'aurait pas de sens.
     *
     * @return string
     */
    public function admin(): string
    {
        return $this->script('htmx')
            . $this->Html->script('/js/chancel-admin.js', ['defer' => true]);
    }

    /**
     * Thèmes d'animation des moodboards, chargés seulement sur les pages
     * concernées. `chancel.js` les détecte via `window.ChancelMoodboard`.
     *
     * @return string
     */
    public function moodboard(): string
    {
        return $this->Html->script('/js/chancel-moodboard.js', ['defer' => true]);
    }

    /**
     * Leaflet, chargé seulement sur la page carte.
     *
     * @return string
     */
    public function carte(): string
    {
        return $this->Html->css(self::CDN . self::LIBS['leaflet_css'])
            . $this->script('leaflet');
    }

    /**
     * @param string $nom Clé dans self::LIBS.
     * @return string
     */
    protected function script(string $nom): string
    {
        return $this->Html->script(self::CDN . self::LIBS[$nom], [
            'crossorigin' => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ]);
    }

    /**
     * Configuration du thème, lue par le compilateur Tailwind du navigateur.
     *
     * Le contenu est injecté plutôt que référencé par `<link>` : le build
     * navigateur de Tailwind ne compile que les blocs
     * `<style type="text/tailwindcss">` présents dans le document.
     *
     * Le fichier de thème ne déclare volontairement aucun import — pas même en
     * commentaire. Tailwind ajoute lui-même sa feuille de base quand il n'en
     * trouve pas, et le navigateur, lui, tente de récupérer tout import de ce
     * bloc relativement à l'URL courante. Le détail est en tête du fichier CSS,
     * et un test garde la règle.
     *
     * @return string
     */
    protected function theme(): string
    {
        if (self::$theme === null) {
            self::$theme = is_file(self::THEME) ? (string)file_get_contents(self::THEME) : '';
        }

        if (self::$theme === '') {
            return '';
        }

        return sprintf('<style type="text/tailwindcss">%s</style>' . "\n", self::$theme);
    }
}
