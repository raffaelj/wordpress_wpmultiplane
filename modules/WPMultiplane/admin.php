<?php

// add link to /cockpit to admin menu
\add_action('admin_menu', function() {
    \add_menu_page('Cockpit', 'Cockpit', 'manage_options', 'cockpit', '', $this->pathToUrl('assets:app/media/logo.svg'), 2);
});

// align icon in admin menu
\add_action('admin_head', function() {
    echo '<style>
#adminmenu .wp-menu-image.dashicons-before img {
    position: absolute;
    height: 1.6em;
    top: 50%;
    margin-top: -.8em;
    left: .5em;
    padding: 0;
}
</style>';
});

// add some vars
$this->on('wpmp.init', function() {

    \add_action('admin_head', function() {
        echo '<script>';
        echo 'var COCKPIT_BASE_URL = "'.COCKPIT_BASE_URL.'";';
        echo 'var COCKPIT_BASE_ROUTE = "'.COCKPIT_BASE_ROUTE.'";';
        echo 'var COCKPIT_UPLOAD_FOLDER = "'.$this->pathToUrl('#uploads:').'";';
        if (isset($this['modules']['videolinkfield'])) {
            echo 'var COCKPIT_VIDEOLINK_ROUTE = "'.COCKPIT_BASE_ROUTE.'/videolinkfield";';
        }
        echo '</script>';
    });

});

// add Wordp link to Cockpit modules menu
$this->helper('admin')->addMenuItem('modules', [
    'label'  => 'WordPress',
    'icon'   => ABSPATH.'/wp-admin/images/wordpress-logo.svg',
    'route'  => '../',
    'active' => false
]);

// set favicon
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
    if (!$this->helper('admin')->favicon) $this->helper('admin')->favicon = $this->pathToUrl(COCKPIT_DIR.'/favicon.png');
}

// fix broken assets paths for App.base() and App.route()
$this->on('app.layout.header', function() {

    $base_url = $this->pathToUrl(COCKPIT_DIR);
    $env_url  = $this->pathToUrl(COCKPIT_ENV_ROOT);

    echo '<script>
        App.base_url = "'.$base_url.'";
        App.env_url  = "'.$env_url.'";
        App.base = function(url) {
            if (url.indexOf("/addons") === 0 || url.indexOf("/config") === 0) {
                return this.env_url+url;
            }
            return this.base_url+url;
        };
        App.route = function(url) {
            if (url.indexOf("/assets") === 0 && url.indexOf("/assetsmanager") !== 0) {
                return this.base_route+"/lib/cockpit"+url;
            }
            if (url.indexOf("/addons") === 0 || url.indexOf("/config") === 0) {
                return this.env_url+url;
            }
            return this.base_route+url;
        };
    </script>';

});
