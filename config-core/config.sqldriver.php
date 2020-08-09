<?php
# Use SQL Driver as main data storage for Cockpit
return [
    'server' => 'sqldriver',
    # Connection options
    'options' => [
        'connection' => 'mysql',                               # One of 'mysql'|'pgsql'
        'host'       => explode(':', DB_HOST, 2)[0],           # Optional, defaults to 'localhost'
        'port'       => explode(':', DB_HOST, 2)[1] ?? '3306', # Optional, defaults to 3306 (MySQL) or 5432 (PostgreSQL)
        'dbname'     => DB_NAME,
        'username'   => DB_USER,
        'password'   => DB_PASSWORD,
        'charset'    => DB_CHARSET,                             # Optional, defaults to 'UTF8'
        'tablePrefix' => 'cp_',
        'bootstrapPriority' => 9999
    ],
    # Connection specific options
    # General: https://www.php.net/manual/en/pdo.setattribute.php
    # MySQL specific: https://www.php.net/manual/en/ref.pdo-mysql.php#pdo-mysql.constants
    'driverOptions' => [
//             \PDO::ATTR_EMULATE_PREPARES => true,
//             \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
//             \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ],
];
