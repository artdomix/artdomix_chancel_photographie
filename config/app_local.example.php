<?php

use function Cake\Core\env;

/*
 * Configuration locale : copier ce fichier en config/app_local.php et l'adapter.
 * app_local.php contient les identifiants de la base et le salt : il est gitignoré
 * et ne doit JAMAIS être committé.
 */
return [
    /*
     * Niveau de debug.
     *
     * Production : false — aucun message d'erreur affiché.
     * Développement : true.
     */
    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),

    /*
     * Chaîne aléatoire utilisée pour le hachage et le chiffrement.
     * À régénérer sur chaque environnement, à traiter comme un secret.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    /*
     * Connexions à la base.
     *
     * Le projet tourne sur MySQL (ou MariaDB) en développement comme en production.
     * On reste sur `utf8mb4_unicode_ci` : la collation `utf8mb4_0900_ai_ci` n'existe
     * que sur MySQL 8 et casserait sur MariaDB ou sur un mutualisé en 5.7.
     */
    'Datasources' => [
        'default' => [
            'host' => env('DB_HOST', 'localhost'),
            //'port' => 'non_standard_port_number',

            'username' => env('DB_USERNAME', 'chancel'),
            'password' => env('DB_PASSWORD', 'chancel'),

            'database' => env('DB_DATABASE', 'chancel'),

            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',

            'url' => env('DATABASE_URL', null),
        ],

        /*
         * Connexion utilisée par la suite de tests.
         *
         * Elle pointe volontairement sur MySQL et non sur SQLite (la valeur par
         * défaut du squelette CakePHP) : le schéma utilise des ENUM et des index
         * FULLTEXT, des tests qui passeraient sur SQLite ne prouveraient rien
         * quant au comportement réel en production.
         */
        'test' => [
            'host' => env('DB_HOST', 'localhost'),
            //'port' => 'non_standard_port_number',
            'username' => env('DB_USERNAME', 'chancel'),
            'password' => env('DB_PASSWORD', 'chancel'),
            'database' => env('DB_TEST_DATABASE', 'chancel_test'),
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'url' => env('DATABASE_TEST_URL', null),
        ],
    ],

    /*
     * Configuration de l'envoi d'e-mails (formulaire de contact, notifications).
     */
    'EmailTransport' => [
        'default' => [
            'host' => env('EMAIL_HOST', 'localhost'),
            'port' => (int)env('EMAIL_PORT', 25),
            'username' => env('EMAIL_USERNAME', null),
            'password' => env('EMAIL_PASSWORD', null),
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Email' => [
        'default' => [
            'from' => env('EMAIL_FROM', 'contact@chancel.art-domix.fr'),
        ],
    ],
];
