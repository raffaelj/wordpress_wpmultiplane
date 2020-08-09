<?php

// Autoload from lib folder (PSR-0)
\spl_autoload_register(function($class) {
    $class_path = __DIR__.'/lib/'.str_replace('\\', '/', $class).'.php';
    if (\file_exists($class_path)) include_once($class_path);
});

define('WPMP_DIR', __DIR__);

if (!defined('WPMP_DATA_DIR'))          define('WPMP_DATA_DIR', __DIR__.'/data');

// not necessary, but if I decide to load Multiplane via config/loadmodules, this constant must be set
define('COCKPIT_DIR', __DIR__.'/lib/cockpit');

// load default config and merge it later with custom config file
define('COCKPIT_CONFIG_PATH', __DIR__.'/config-core/config.php');

if (!class_exists('DotEnv')) {
    include(COCKPIT_DIR.'/lib/DotEnv.php');
}

// load .env file if exists
DotEnv::load(__DIR__);

// check for custom defines
if (\file_exists(__DIR__.'/defines.php')) {
    include(__DIR__.'/defines.php');
}

//-----------------------------------------------------------------------------
// Cockpit

$BASE_URL = dirname(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH));

if (!defined('WP_ADMIN_ROUTE'))         define('WP_ADMIN_ROUTE', '/wp-admin');
// if (!defined('COCKPIT_ENV_ROOT'))       define('COCKPIT_ENV_ROOT', __DIR__);
if (!defined('COCKPIT_ENV_ROOT'))       define('COCKPIT_ENV_ROOT', WPMP_DATA_DIR.'/cp');
if (!defined('COCKPIT_BASE_ROUTE'))     define('COCKPIT_BASE_ROUTE', WP_ADMIN_ROUTE.'/cockpit');
if (!defined('COCKPIT_BASE_URL'))       define('COCKPIT_BASE_URL', $BASE_URL === '/' ? '' : $BASE_URL);
if (!defined('COCKPIT_ADMIN'))          define('COCKPIT_ADMIN', strpos($_SERVER['REQUEST_URI'], WP_ADMIN_ROUTE) === 0 ? 1 : 0);
if (!defined('COCKPIT_ADMIN_WP'))       define('COCKPIT_ADMIN_WP', strpos($_SERVER['REQUEST_URI'], COCKPIT_BASE_ROUTE) === 0 ? 1 : 0);

// bootstrap cockpit
require_once(COCKPIT_DIR.'/bootstrap.php');

//-----------------------------------------------------------------------------
// CpMultiplane

if (!defined('MP_DOCS_ROOT'))           define('MP_DOCS_ROOT', __DIR__.'/lib/cpmultiplane');
// if (!defined('MP_ENV_ROOT'))            define('MP_ENV_ROOT', __DIR__.'/multiplane');
if (!defined('MP_ENV_ROOT'))            define('MP_ENV_ROOT', WPMP_DATA_DIR.'/mp');

// prevent overrides and route bindings
if (!defined('MP_SELF_EXPORT'))         define('MP_SELF_EXPORT', 1);

// fix wrong base url detection
if (!defined('MP_BASE_URL'))            define('MP_BASE_URL', $cockpit->pathToUrl(MP_DOCS_ROOT));

// bootstrap CpMultiplane
require_once(MP_DOCS_ROOT.'/bootstrap.php');

// revert layout from theme
$cockpit->layout = null;

//-----------------------------------------------------------------------------
// detect route for route bindings

if (!defined('COCKPIT_ADMIN_ROUTE')) {

    // if user is inside admin area and is logged in,
    // bind routes to /wp-admin/cockpit/*
    if (COCKPIT_ADMIN) {

        if (strpos($_SERVER['REQUEST_URI'], COCKPIT_BASE_ROUTE) === 0) {

            $route = preg_replace('#'.preg_quote(COCKPIT_BASE_ROUTE, '#').'#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1);
            define('COCKPIT_ADMIN_ROUTE', $route == '' ? '/' : $route);

        } else {
            define('COCKPIT_ADMIN_ROUTE', null);
        }
    }

    // bind routes to /*
    else {
        $route = preg_replace('#'.preg_quote(COCKPIT_BASE_URL, '#').'#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1);
        define('COCKPIT_ADMIN_ROUTE', $route == '' ? '/' : $route);
    }
}

cockpit()->set('route', COCKPIT_ADMIN_ROUTE);

//-----------------------------------------------------------------------------
// trigger event after all modules are loaded, but before helpers are initiated
$cockpit->trigger('wpmp.bootstrap');

//-----------------------------------------------------------------------------
// init helpers
$cockpit('wp'); // init WP/CP mappings
$cockpit('settings')->init();


// run app
\add_action('init', function() {
    cockpit()->trigger('wpmp.init')->run();
}, 100);
