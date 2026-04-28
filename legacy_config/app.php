<?php
/**
 * Gridbase Digital Solutions - Application Configuration
 */

return [
    'name'      => 'Gridbase Invoice System',
    'version'   => '1.0.0',
    'timezone'  => 'America/Santo_Domingo',
    'locale'    => 'es',
    'debug'     => false,
    
    // Base URL - update for your subdomain
    'base_url'  => 'https://bills.gridbase.com.do',
    
    // Session configuration
    'session' => [
        'name'     => 'gridbase_session',
        'lifetime' => 7200, // 2 hours
        'secure'   => true,
        'httponly'  => true,
        'samesite' => 'Strict',
    ],
    
    // JWT Secret for API auth (change this to a random string)
    'jwt_secret' => 'CHANGE_THIS_TO_A_RANDOM_64_CHAR_STRING',
    
    // File storage paths
    'storage' => [
        'invoices' => __DIR__ . '/../storage/invoices/',
        'logs'     => __DIR__ . '/../storage/logs/',
    ],
    
    // Pagination defaults
    'per_page' => 20,
];
