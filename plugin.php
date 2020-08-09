<?php
/**
 * Plugin Name: WPMultiplane
 * Plugin URI:
 * Description: Implement Cockpit CMS and CpMultiplane into WordPress - I'm not sure, if this is incredibly beautiful or a very bad idea.
 * Author: Raffael Jesche
 * Author URI: https://www.rlj.me
 * Version: 0.1.0
 * License: MIT
 * License URI: 
 *
 */

if (!defined('ABSPATH')) exit;

\register_activation_hook(__FILE__, function() {
    require_once(__DIR__ . '/activate.php');
});
\register_deactivation_hook(__FILE__, function() {
    require_once(__DIR__ . '/deactivate.php');
});

// bootstrap
require_once(__DIR__ . '/bootstrap.php');
