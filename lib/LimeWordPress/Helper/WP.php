<?php

namespace LimeWordPress\Helper;

/**
 * Class Settings
 * @package Lime\Helper
 */
class WP extends \Lime\Helper {

    protected $assetsCounter = 0;

    public function initialize() {

        $this->mapRoutes();
        $this->mapEvents();

        // double check to prevent dispatching route bindings without login
        $this->app->on('before', function() {

            $allowedRoutes = [
                '/check-backend-session',
                '/auth/check',
            ];

            if (COCKPIT_ADMIN_WP && !\is_user_logged_in() && !in_array(COCKPIT_ADMIN_ROUTE, $allowedRoutes)) {
                $this->response->status = 401;
            }
        });

        \add_action('wp_logout', function() {
            $app = $this->app;
            $this->app->module('cockpit')->logout();

        });

    }

    public function getLoginUrl() {

        return \wp_login_url();

    }

    public function getLogoutUrl() {

        $args = [];

        if ($redirect = $this->app->param('to', false)) {
            $args['redirect_to'] = $redirect;
        }

        $logout_url = \add_query_arg($args, \site_url('wp-login.php?action=logout', 'login'));
        $nonce_url  = \add_query_arg('_wpnonce', \wp_create_nonce('log-out'), $logout_url);

        return $nonce_url;
    }

    public function setUser() {

        $user = $this->mapUser();

        // if not false, logging out via WP in a different tab worked in general, but the
        // `/check-backend-session` call returned true until the next page reload
        $permanent = false;

        $this->app->module('cockpit')->setUser($user, $permanent);

        // the user data wasn't available while bootstrapping
        $extract = $this->app->helper('admin')->data->get('extract');
        $extract['user'] = $user;
        $this->app->helper('admin')->data['extract'] = $extract;

        return $user;

    }

    public function mapUser($wp_user = false) {

        if (!$wp_user) $wp_user = \wp_get_current_user();

        if (!$wp_user instanceof \WP_User) return false;

        // to do: better role mappings
        $group = in_array('administrator', $wp_user->roles) ? 'admin' : $wp_user->roles[0] ?? null;

        $user = [
            'user'   => $wp_user->user_login,
            'name'   => $wp_user->display_name,
            'wp_id'  => $wp_user->ID,
            '_id'    => $wp_user->ID,
            'email'  => $wp_user->user_email,
            'active' => $wp_user->user_status,
            'group'  => $group,
            'i18n'   => $wp_user->locale
        ];

        if ($user && !$this->module('cockpit')->hasaccess('cockpit', 'backend', @$user['group'])) {
            $user = null;
        }

        return $user;

    }

    public function mapRoutes() {

        // redirect cp login/logout to wp login/logout
        $this->app->bind('/auth/login', function() {
            $this->reroute($this('wp')->getLogoutUrl());
        });

        // login into WP via CP session/login modal
        $this->app->bind('/auth/check', function() {

            $data = $this->param('auth');

            if (!$data) return false;

            $credentials = [
                'user_login'    => $data['password'],
                'user_password' => $data['user'],
            ];

            // https://developer.wordpress.org/reference/functions/wp_signon/
            $wp_user = \wp_signon($credentials);

            $user = $wp_user instanceof \WP_Error ? false : $this('wp')->mapUser($wp_user);

            if (!$this->helper('csfr')->isValid('login', $this->param('csfr'), true)) {
                return ['success' => false, 'error' => 'Csfr validation failed'];
            }

            if ($this->request->is('ajax')) {
                return $user ? ['success' => true, 'user' => $user] : ['success' => false, 'error' => 'User not found'];
            } else {
                $this->reroute('/');
            }

        });


        // redirect empty account page to wp profile page
        $this->app->bind('/accounts/account', function() {
            $this->reroute(\site_url('wp-admin/profile.php'));
        });

    }

    public function mapEvents() {

        // https://codex.wordpress.org/Plugin_API/Action_Reference

        // Fires after WordPress has finished loading but before any headers are sent.
        \add_action('init', function() {

            $this->app->trigger('wp.init');

            if (COCKPIT_ADMIN && \is_user_logged_in()) {
                $this->setUser();
                $this->app->trigger('admin.init');
            }

        }, 0);


        // Executes after the query has been parsed and post(s) loaded, but before any template execution, inside the main WordPress function wp(). Useful if you need to have access to post data but can't use templates for output. Action function argument: WP object ($wp) by reference.
        \add_action('wp', function() {
            $this->app->trigger('wp');
        });

//         \add_action('wp_enqueue_scripts', function() {
//             $this->app->trigger('wp.enqueue.scripts');
//         });

    }

    public function addAssets($src = '', $options = null) {

        // https://developer.wordpress.org/reference/functions/wp_register_style/
        // https://developer.wordpress.org/reference/functions/wp_register_script/

        if (empty($src)) return;

        $debug  = $this->retrieve('debug');

        if (\is_string($options)) {
            $handle  = $options;
            $options = [];
        }

        $handle  = $handle ?? $options['handle'] ?? 'wpmultiplane_'.$this->assetsCounter++;
        $version = $options['version'] ?? ($debug ? time() : false);
        $deps    = $options['deps'] ?? [];


        if (@\substr($src, -3) == '.js') {

            $in_footer = $options['footer'] ?? true;

            \add_action('wp_enqueue_scripts', function() use ($handle, $src, $deps, $version, $in_footer) {
                \wp_register_script($handle, $src, $deps, $version, $in_footer);
                \wp_enqueue_script($handle);
            });
        }

        elseif (@\substr($src, -4) == '.css') {

            $media   = $options['media'] ?? 'all';
            \add_action('wp_enqueue_scripts', function() use ($handle, $src, $deps, $version, $media) {
                \wp_register_style($handle, $src, $deps, $version, $media);
                \wp_enqueue_style($handle);
            });
        }

    }

}
