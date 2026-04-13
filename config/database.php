<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    | PostgreSQL digunakan sebagai default untuk user, sistem, kebun, lab QA.
    */

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        /*
        |----------------------------------------------------------------------
        | PostgreSQL — User Management, Sistem, Peta Kebun, Lab QA
        |----------------------------------------------------------------------
        */
        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('DATABASE_URL'),
            'host'           => env('DB_PGSQL_HOST', '127.0.0.1'),
            'port'           => env('DB_PGSQL_PORT', '5432'),
            'database'       => env('DB_PGSQL_DATABASE', 'madukismo_system'),
            'username'       => env('DB_PGSQL_USERNAME', 'postgres'),
            'password'       => env('DB_PGSQL_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => 'prefer',
        ],

        /*
        |----------------------------------------------------------------------
        | SQL Server — Data Dashboard Tanaman (Read & Write)
        |----------------------------------------------------------------------
        */
        'sqlsrv' => [
            'driver'         => 'sqlsrv',
            'url'            => env('SQLSRV_URL'),
            'host'           => env('DB_SQLSRV_HOST', 'localhost'),
            'port'           => env('DB_SQLSRV_PORT', '1433'),
            // Default database for SQL Server connection set to TIMB_MK
            'database'       => env('DB_SQLSRV_DATABASE', 'TIMB_MK'),
            'username'       => env('DB_SQLSRV_USERNAME', 'sa'),
            'password'       => env('DB_SQLSRV_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'encrypt'        => env('DB_SQLSRV_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_SQLSRV_TRUST_CERT', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table'       => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', \Illuminate\Support\Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],
        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
