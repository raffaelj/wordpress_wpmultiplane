<?php
return [
    'db' => [
        'host'     => explode(':', DB_HOST, 2)[0],
        'port'     => explode(':', DB_HOST, 2)[1] ?? '3306',
        'dbname'   => DB_NAME,
        'user'     => DB_USER,
        'password' => DB_PASSWORD,
        'charset'  => DB_CHARSET,
    ],
];
