<?php
/**
 * Privacy/Cookie Popup
 * 
 * @version   0.1.0
 * @author    Raffael Jesche
 * @license   MIT
 */


\add_action('wp_footer', function() {

    $path   = $this->path('#storage:privacy/privacy-notice.php');
    $locale = $this('i18n')->locale;

    if ($localizedFile = $this->path("#storage:privacy/privacy-notice_{$locale}.php")) {
        $path = $localizedFile;
    }

    // default fallback
//     if (!$path) $path = $this->path('privacy:views/partials/privacy-notice.php');
    if (!$path) $path = $this->path('multiplane:themes/rljbase/views/partials/privacy-notice.php');

    $this->renderView($path);

}, 0);


// mp.js
\add_action('wp_head', function() {
    echo '<script>MP_BASE_URL = "'.MP_BASE_URL.'";MP_POLYFILLS_URL = "'.$this->pathToUrl('multiplane:themes/rljbase/assets/js/polyfills.min.js').'";</script>';
;
});

\add_action('wp_footer', function() {

    echo '<script src="'.$this->pathToUrl('multiplane:themes/rljbase/assets/js/mp.min.js').'"></script>';

    echo '<script>'
        . 'MP.ready(function() {'
        . 'MP.Lightbox.init({group:".wp-block-gallery,.lightbox-gallery",selector:"a"});'
        . 'MP.convertVideoLinksToIframes();'
        . '});'
        . '</script>';
// }, 20); // load after wp_enqueue_scripts hook
}, 1); // load after privacy notice and before header/footer plugin


// only backend
\add_action('init', function() {
    if (COCKPIT_ADMIN) {
        include_once(__DIR__.'/admin.php');
    }
});
