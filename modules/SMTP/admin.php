<?php

$this('settings')->addOptions([
    [
        'id'      => 'from',
        'title'   => 'From',
    ],
    [
        'id'      => 'from_name',
        'title'   => 'From Name',
    ],
    [
        'id'      => 'transport',
        'title'   => 'Transport',
//         'args' => [
//             'default' => 'smtp',
//         ],
    ],
    [
        'id'      => 'host',
        'title'   => 'Host',
    ],
    [
        'id'      => 'user',
        'title'   => 'User',
    ],
    [
        'id'      => 'password',
        'title'   => 'Password',
        'type'    => 'password',
    ],
    [
        'id'      => 'port',
        'title'   => 'Port',
        'args' => [
            'type' => 'integer',
//             'default' => 587,
        ]
    ],
    [
        'id'      => 'auth',
        'title'   => 'Auth',
        'type' => 'boolean',
//         'args' => [
//             'default' => true,
//         ]
    ],
    [
        'id'      => 'encryption',
        'title'   => 'Encryption',
    ],
], 'smtp');
