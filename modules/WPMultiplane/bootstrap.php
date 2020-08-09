<?php

$this->module('wpmultiplane')->extend([
    'description' => 'Bridge between Cockpit modules and WordPress',
]);

// enable forms
$this->on('wpmp.init', function() {

    if ( !isset($this['modules']['forms'])
      || !isset($this['modules']['formvalidation'])
      || !isset($this['modules']['multiplane'])
        ) {
        return;
        }

    $this->bind('/form/submit/*', function($params) {
        return $this->invoke('Multiplane\\Controller\\Forms', 'submit', ['params' => $params[':splat'][0]]);
    });

    // add form shortcode
    \add_shortcode('wpmp-form', function($atts) {

        \extract(\shortcode_atts([
            'name'  => 'contact',
            'title' => '',
            'id'    => 'contact',
            'class' => null,
        ], $atts));

        $options = [
            'title' => $title,
            'id'    => $id,
        ];

        // form helper 'Multiplane\\Controller\\Forms'
        return '<aside class="form' . ($class ? ' ' . trim($class) : '') . '">' . $this('form')->form($name, $options) . '</aside>';

    });

    // init + load i18n, compatible with Polylang plugin - it changes the locale, but that info is only available after wp hook
    \add_action('wp', function() {
        $locale = \get_locale();
        if ($translationspath = $this->path(MP_CONFIG_DIR."/i18n/{$locale}.php")) {
            $this('i18n')->load($translationspath, $locale);
            $this('i18n')->locale = $locale;
        }
    });

});


// add css for videolink and privacy notice
$this->on('wpmp.init', function() {
    if (isset($this['modules']['videolinkfield'])) {
        $this('wp')->addAssets($this->pathToUrl('wpmultiplane:assets/css/style.min.css'), 'videolink');
    }
});

// add module descriptions for settings overview page
$this->on('wpmp.bootstrap', function() {

    $descriptions = [
        'cockpit'        => 'Cockpit core module - can not be disabled',
        'collections'    => 'Collections is a powerful feature that comes with Cockpit. With collections you can manage different types of content lists. (Part of Cockpit core modules)',
        'forms'          => 'Forms are a great way to receive input from your users. It is usually painful to set up a server-side form processing script, and that\'s where Cockpit forms come to help. (Part of Cockpit core modules)',
        'singletons'     => 'Singletons are like single collection entries. (Part of Cockpit core modules)',
        'tables'         => 'SQL table manager for Cockpit CMS',
        'videolinkfield' => 'Video thumbnail downloader from YouTube and Vimeo for Cockpit CMS',
        'multiplane'     => 'Core module of the CpMultiplane frontend',
        'sqldriver'      => 'SQL Driver for Cockpit CMS',
    ];

    foreach ($descriptions as $module => $description) {
        if (isset($this['modules'][$module]) && !isset($this->module($module)->description)) {
            $this->module($module)->extend([
                'description' => $description,
            ]);
        }
    }

});


// only backend
\add_action('init', function() use($app) {
    if (COCKPIT_ADMIN) {
        include_once(__DIR__.'/admin.php');
    }
});
