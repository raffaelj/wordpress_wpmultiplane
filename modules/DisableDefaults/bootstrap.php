<?php

$this->module('disabledefaults')->extend([

    'name'        => 'disabledefauts',
    'title'       => 'Disable Defaults',
    'description' => 'Disable multiple (bad) design decisions of WordPress (wp_ob_end_flush_all, redirect_canonical, autoembed, xmlrpc)',
    // 'hasSettings' => true,

]);

// disable gravatar
\add_filter('get_avatar', function() { return ''; });

// disable fix for older PHP version on shutdown to prevent errors when using ob_get_clean() etc.
// source: https://stackoverflow.com/questions/38693992/notice-ob-end-flush-failed-to-send-buffer-of-zlib-output-compression-1-in
remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );


// disable auto-guessing of inclompete urls
// https://core.trac.wordpress.org/ticket/16557
// https://wordpress.org/plugins/disable-url-autocorrect-guessing/
\add_filter('redirect_canonical', function($url) {
    if (\is_404() && !isset($_GET['p']) ) return false;
    return $url;
});


// disable autoembed
// source: https://www.itsupportguides.com/knowledge-base/tech-tips-tricks/wordpress-how-to-disable-tinymce-auto-embed-of-urls-youtubetwitterfacebook-etc/

\add_action('init', function() {

    // Remove the REST API endpoint.
    \remove_action('rest_api_init', 'wp_oembed_register_route');

    // Turn off oEmbed auto discovery.
    \add_filter('embed_oembed_discover', '__return_false');

    // Don't filter oEmbed results.
    \remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

    // Remove oEmbed discovery links.
    \remove_action('wp_head', 'wp_oembed_add_discovery_links');

    // Remove oEmbed-specific JavaScript from the front-end and back-end.
    \remove_action('wp_head', 'wp_oembed_add_host_js');
    // add_filter( 'tiny_mce_plugins', 'disable_embeds_tiny_mce_plugin' );

    // Remove all embeds rewrite rules.
    \add_filter('rewrite_rules_array', 'disable_embeds_rewrites');

    // Remove filter of the oEmbed result before any HTTP requests are made.
    \remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);

    // remove wp-json link in head
    \remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );

}, 9999);


// Disable use XML-RPC
\add_filter('xmlrpc_enabled', '__return_false');
\remove_action('wp_head', 'rsd_link');


// disable all autoembeds
// remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );


// change output of auto-generated iframes
// has no effect, if globally disabled
// function change_automatic_embeds( $html, $url, $attr, $post_id ) {

    // $out  = print_r($url, true) . '<br>';
    // $out .= $post_id . '<br>';
    // $out .= print_r($attr, true) . '<br>';
    // $out .= htmlentities($html);

    // return $out;
// }
// add_filter( 'embed_oembed_html', 'change_automatic_embeds', 99, 4 );

// add_filter('pre_render_block', function($block) {
    // var_dump($block);
// });
