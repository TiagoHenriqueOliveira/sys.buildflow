<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            // Aponta para public/midia (diretório real, não symlink): este
            // ambiente de hospedagem (Plesk) não consegue criar/manter o link
            // simbólico public/storage -> storage/app/public sem SSH, E ALÉM
            // DISSO bloqueia no nível do servidor (fora do .htaccess) qualquer
            // pasta chamada literalmente "storage" — por isso o nome "midia".
            // Local único e real para todos os anexos/assinaturas/fotos.
            'root' => public_path('midia'),
            'url' => env('APP_URL').'/midia',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    // Sem link simbólico: o disco 'public' já grava e lê direto em
    // public/midia (ver acima) — não há nada para o `storage:link` linkar.
    // Acesso público a public/midia é bloqueado por .htaccess
    // (Require all denied); a única forma de servir esses arquivos é a rota
    // GET /midia/{path} (StorageController), que exige autenticação.
    'links' => [],

];
