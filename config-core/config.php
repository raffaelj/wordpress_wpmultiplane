<?php
/**
 * This config file contains the data do setup cockpit correctly with mixed modules folders and sql driver.
 *
 * Don't change this file! Also your changes would get lost with the next plugin update.
 *
 * If you want to customize settings, use the config file in the config folder.
 *
 */

// merge/replace default config with custom config in data/cp/config/config.php
$_configpath = COCKPIT_CONFIG_DIR.'/config.'.(file_exists(COCKPIT_CONFIG_DIR.'/config.php') ? 'php':'yaml');
$customConfig = [];
if (file_exists($_configpath)) {
    $customConfig = preg_match('/\.yaml$/', $_configpath) ? Spyc::YAMLLoad($_configpath) : include($_configpath);
}

// `array_replace` was chosen intentionally over `array_replace_recursive`.
// To enable all modules, add an empty key `'modules.disabled' => [],` to your custom config file.
$config = array_replace([

    'app.name' => 'WPMultiplane',

    'loadmodules' => [
        WPMP_DIR.'/modules',
    ],

    'modules.disabled' => [
//         'Cockpit',
//         'Collections',
//         'Forms',
//         'Singletons',

//         'DisableDefaults',
//         'SMTP',
//         'WPMultiplane',
//         'Privacy',
//         'SqlDriver',
        'Tables',
//         'rlj-blocks',
//         'test',
//         'FormValidation',
//         'VideoLinkField',
//         'Multiplane',
    ],

    'groups' => [
        'author' => [
            'cockpit' => [
                'backend' => true
            ],
        ],
        'subscriber' => [
            'cockpit' => [
                'backend' => false
            ],
        ],
    ],

], $customConfig);

if (!isset($config['tables']) && !in_array('Tables', $config['modules.disabled'])) {
    $config['tables'] = include(__DIR__.'/config.tables.php');
}

if (!isset($config['database']) && !in_array('SqlDriver', $config['modules.disabled'])) {
    $config['database'] = include(__DIR__.'/config.sqldriver.php');
}

return $config;
