<?php

/**
 * To do:
 * - [x] sanitize values, e.g. boolean
 * 
 * debugging, notes...
 * 
 * Error: Options Page Not Found" - https://wordpress.stackexchange.com/a/284111
 * 
 */

namespace LimeWordPress\Helper;

/**
 * Class Settings
 * @package Lime\Helper
 */
class Settings extends \Lime\Helper {

    public $sections = [];
    public $modules  = [];
    public $page     = 'wpmultiplane';
    public $group    = 'wpmultiplane';
    public $prefix   = 'wpmultiplane_';

    public $isCodeMirrorEnabled = false;

//     public function initialize() {
    public function init() {

        // overwrite config
        $config = $this->app->retrieve('settings', []);
        foreach ($config as $key => $val) {
            $this->{$key} = $val;
        }

        // add sub menu page under "Settings"
        \add_action('admin_menu', function() {

            // https://developer.wordpress.org/reference/functions/add_menu_page/
            \add_menu_page(
                'WPMultiplane modules',         // page title/h1
                'WPMultiplane',                 // menu title
                'manage_options',               // required permissions
                $this->page,                    // url query string `?page=wpmultiplane`
                [$this, 'displayMainSettings']
            );

            foreach ($this->modules as $module => $v) {

                $name        = strtolower($module);
                $title       = $this->app->module($name)->title ?? $module;
                $description = $this->app->module($name)->description ?? null;

                $this->addOption(
                    [
                        'id'          => $name,
                        'title'       => $title,
                        'type'        => 'boolean',
                        'description' => $description,
                    ],
                    $this->page,      // page
                    'modules_enabled' // section
                );

            }

            // create sub pages for all modules with settings
            $modules = array_keys((array)$this->app['modules']);

            foreach ($modules as $module) {

                $title = $this->app->module($module)->title ?? $module;

                if ($this->app->module($module)->hasSettings ?? false) {
                    $pageHook = $this->addSubPage(
                        $module,
                        $title
                    );
                    $this->modules[$module]['pageHook'] = $pageHook;

                }

            }

            // remove first sub menu duplicate --> also changes the url of the parent page to the next sub page :-(
//             \remove_submenu_page($this->page, $this->page);

        }, 10);

        \add_action('admin_init', function() {

            // create section to dis/enable modules
            \add_settings_section(
                'modules_enabled',
                __('Enable Modules'),
                null,
                $this->page
//                 $this->group
            );
            $this->sections['modules_enabled'] = true;

            // create settings sections for all modules
            $modules = array_keys((array)$this->app['modules']);

            foreach ($modules as $module) {

                $title = $this->app->module($module)->title ?? $module;

                if ($this->app->module($module)->hasSettings ?? false) {

                    $this->addSection([
                        'name' => $module,
                        'title' => $title,
                        'group' => $module
                    ]);
                }

            }

        }, 10);

    }

    public function addOptions($settings = [], $page = null, $section = null) {

        foreach($settings as $options) {
            $this->addOption($options, $page, $section);
        }

    }

    public function addOption($options = [], $page = null, $section = null) {

        $options = array_replace_recursive([

            'group'      => !empty($options['group']) ? $options['group'] : ($page ?? $this->group),
            'name'       => null,
            'args'       => [],

            'id'         => '',
            'title'      => null,
            'callback'   => null,
            'section'    => null,
            'field_args' => null,
            'type'       => 'string',

            'section_callback' => null,

        ], $options);

        if (!$options['section']) {
            $options['section'] = $section ? $section : ($page ? $page : $this->group);
        }

        if (!$options['name']) {
            // used for input name ... name="wpmultiplane_smtp[from]"
            $options['name'] = $this->prefix . $options['section'];
        }

        if (!$options['title']) {
            $options['title'] = $options['id'];
        }

        if (!$options['field_args']) {
            $options['field_args'] = [
                'id'          => $options['id'],
                'label_for'   => $options['name'] . '_' . $options['id'],
                'name'        => $options['name'],
                'type'        => $options['type'],
                'description' => $options['description'] ?? null,
            ];
        }

        if (isset($options['type']) && !isset($options['args']['type'])) {
            // allowed types
            if (in_array($options['type'], ['string', 'boolean', 'integer', 'number'])) {
                $options['args']['type'] = $options['type'];
            }
        }

        if (!$options['callback']) {
            $options['callback'] = [$this, 'displayOption'];
        }

        if (!isset($options['args']['sanitize_callback'])) {
            $options['args']['sanitize_callback'] = $this->sanitizeCallback($options['type'], $options['id']);
        }

        if (isset($options['storage'])) {
            $options['field_args']['storage'] = $options['storage'];
        }

        if (isset($options['info'])) {
            $options['field_args']['info'] = $options['info'];
        }

        \add_action('admin_init', function() use($options) {

            $identifier = $options['group'] == $this->group ? $options['group'] : $this->prefix . $options['group'];

            // https://developer.wordpress.org/reference/functions/register_setting/
            \register_setting(
                $identifier,
                $options['name'],
                $options['args']
            );

            // https://codex.wordpress.org/Function_Reference/add_settings_field
            \add_settings_field(
                $options['id'],
                __($options['title'], $options['group']),
                $options['callback'],
                $identifier,
                $options['section'],
                $options['field_args']
            );

            // bypass db and write file to #storage:
            if (isset($options['storage'])) {

                if (!\get_option($identifier)) {

                    // https://developer.wordpress.org/reference/hooks/update_option/
                    // https://developer.wordpress.org/reference/hooks/add_option/
                    \add_action('add_option', function($opt, $new) use ($identifier, $options) {

                        if ($opt != $identifier) return;

                        if (!isset($new[$options['id']])) return;

                        if (!$this('fs')->write($options['storage'], $new[$options['id']])) {
                            return;
                        }

                        // ugly hack, but it works...
                        // if writing to #storage was successfull, remove the entry from database
                        // (I don't know, how to bypass the db storage completely)

                        \add_action("add_option_{$opt}", function($opt) {

                            \delete_option($opt);

                        });

                    }, 10, 2); // 2 is the number of args - if not set, only the first arg is available

                }
                else {

                    // also drop existing key if already present in db
                    \add_action('update_option', function($opt, $old, $new) use ($identifier, $options) {
                        if ($opt != $identifier) return;
                        if (!isset($new[$options['id']])) return;
                        if (!$this('fs')->write($options['storage'], $new[$options['id']])) {
                            return;
                        }
                        \add_action("updated_option", function($opt) {
                            \delete_option($opt);
                        });
                    }, 10, 3);

                }

            }
// echo $options['section']."\n";
            if (!isset($this->sections[$options['section']])) {
// echo "no section\n";
                $this->addSection($options);
            }

            if ($options['type'] == 'code') {
                $pageHook = "{$this->page}_page_{$identifier}";

                $pageHook = $this->modules[$options['group']]['pageHook'] ?? false;
                $type = $options['code_type'] ?? 'php';

                if ($pageHook) $this->enqueueCodeMirror($pageHook, $type);
            }

        }, 30);

    }

    public function addSection($options) {

        if (!isset($options['name'])) return;

        \add_action('admin_init', function() use ($options) {

//             $section = $options['section'] ?? $options['name'];
            $section = $options['name'];

            // https://codex.wordpress.org/Function_Reference/add_settings_section
            \add_settings_section(
//                 $options['name'],
                $section,
                \__($options['title'] ?? $options['name']),
                $options['callback'] ?? null,
                $this->prefix . ($options['group'] ?? $this->group)
            );

//             $this->sections[$options['name']] = true;
            $this->sections[$section] = true;

        }, 20);

    }

    public function addSubPage($name = '', $title = '', $callback = null) {

        return \add_submenu_page(
            $this->page,
            \__($title),
            \__($title),
            'manage_options',
            $this->prefix . $name,
            [$this, 'displaySettings']
        );

    }

    public function displayOption($args) {

        if (isset($args['storage'])) {
            // bypass db request and read the stored file
            $options = [
                $args['id'] => $this->app->helper('fs')->read($args['storage'])
            ];
        }
        else {
            $options = \get_option($args['name']);
        }

        // This wrapper always assumes an array
        if (!is_array($options)) $options = [];

        if (!isset($options[$args['id']])) {
            $options[$args['id']] = '';
        }

        $id   = \esc_attr($args['name']) . '_' . \esc_attr($args['id']);
        $name = \esc_attr($args['name']) . '[' . \esc_attr($args['id']) .']';

        switch($args['type']) {

            case 'boolean':
                echo '<input type="checkbox" id="'.$id.'" name="'.$name.'" value="1"' . \checked(1, $options[$args['id']], false) . '/>';
                break;

            case 'password':
                echo '<input type="password" id="'.$id.'" name="'.$name.'" value="' . $options[$args['id']] . '" />';
                break;

            case 'textarea':
                echo '<textarea id="'.$id.'" name="'.$name.'" style="width:90%;" rows="10">';
                echo $options[$args['id']];
                echo '</textarea>';
                break;

            case 'code':
                echo '<textarea id="'.$id.'" name="'.$name.'" style="width:90%;font-family:monospace;" rows="15">';
                echo $options[$args['id']];
                echo '</textarea>';
                if ($this->isCodeMirrorEnabled) {
                    echo "<script>jQuery(document).ready(function($) {wp.codeEditor.initialize($('#$id'), cm_settings);});</script>";
                }
                break;

            default:
                echo '<input type="text" id="'.$id.'" name="'.$name.'" value="' . $options[$args['id']] . '" />';

        }

    }

    public function displaySettings() {

        $page = $_GET['page'];

        \settings_errors($this->prefix . 'messages');

        echo '<h1>WPMultiplane</h1>';

        echo '<form action="options.php" method="post">';

        \settings_fields($page);

        $this->do_settings_sections($page);

        \submit_button(__('Save Changes'));

        echo '</form>';

    }

    public function displayMainSettings() {

        \settings_errors($this->prefix . 'messages');

        echo '<h1>' . \esc_html(\get_admin_page_title()) . '</h1>';

        echo '<p>Dis- or enabling modules on this option page has no effect. In general, it is implemented, but I deactivated that option in the source code. I\'m not sure, if I keep that page or if I\'ll transform it to a simple module overview page without any options.</p>';

        echo '<form action="options.php" method="post">';

        \settings_fields($this->group);

        $this->do_settings_sections($this->group);
//         $this->do_settings_sections($this->page);

//         \submit_button(__('Save Changes'));

        echo '</form>';

    }

    public function sanitizeCallback($type, $k) {

        // return a sanitize_callback for serialized data

        switch($type) {

            case 'boolean':

                  return function($v) use ($k) {
                      if (!is_array($v)) return (bool) $v;
                      if (isset($v[$k])) $v[$k] = (bool) $v[$k];
                      return $v;
                  };
                  break;

            case 'integer' : 

                  return function($v) use ($k) {
                      if (!is_array($v)) return intval($v);
                      if (isset($v[$k])) $v[$k] = intval($v[$k]);
                      return $v;
                  };
                  break;

            case 'number' : 

                  return function($v) use ($k) {
                      if (!is_array($v)) return floatval($v);
                      if (isset($v[$k])) $v[$k] = floatval($v[$k]);
                      return $v;
                  };
                  break;

            case 'string' : 
                  return function($v) use ($k) {
                      if (!is_array($v)) return trim($v);
                      if (isset($v[$k])) $v[$k] = trim($v[$k]);
                      return $v;
                  };
                  break;

        }

        return null;

    }

    // copy/paste from /wp-admin/includes/template.php - do_settings_sections()
    public function do_settings_sections($page, $options = []) {

        global $wp_settings_sections, $wp_settings_fields;

        if ( ! isset( $wp_settings_sections[ $page ] ) ) {
            return;
        }

        foreach ( (array) $wp_settings_sections[ $page ] as $section ) {

            if (!isset($options['title']) || $options['title'] == true) {
                if ( $section['title'] ) {
                    echo "<h2>{$section['title']}</h2>\n";
                }
            }

            if ((!isset($options['description']) || $options['description'] == true) && strpos($page, $this->prefix) === 0) {
                $module = str_replace($this->prefix, '', $page);

                if (isset($this->app['modules'][$module])
                    && $description = $this->app->module($module)->description) {
                    echo '<p>' . $description . '</p>';
                }
            }

            if ( $section['callback'] ) {
                \call_user_func( $section['callback'], $section );
            }

            if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
                continue;
            }
            echo '<table class="form-table" role="presentation">';

            $this->do_settings_fields( $page, $section['id'] );

            echo '</table>';
        }

    }

    // copy/paste from /wp-admin/includes/template.php - do_settings_fields()
    public function do_settings_fields( $page, $section ) {

        global $wp_settings_fields;

        if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
            return;
        }

        foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {

            $class = '';
            $cols  = 2;

            if ( ! empty( $field['args']['class'] ) ) {
                $class = ' class="' . \esc_attr( $field['args']['class'] ) . '"';
            }

            echo "<tr{$class}>";

            if ( ! empty( $field['args']['label_for'] ) ) {
                echo '<th scope="row"><label for="' . \esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></th>';
            } else {
                echo '<th scope="row">' . $field['title'] . '</th>';
            }

            echo '<td>';
            \call_user_func( $field['callback'], $field['args'] );
            echo '</td>';

            if (!empty($field['args']['description'])) {
                echo '<td>' . \esc_attr($field['args']['description']) . '</td>';
                $cols++;
            }

            echo '</tr>';

            if (!empty($field['args']['info'])) {
                echo '<tr><td colspan="'.$cols.'">' . $field['args']['info'] . '</td></tr>';
            }

        }

    }

    public function enqueueCodeMirror ($pageHook, $type = 'php') {

        \add_action('admin_enqueue_scripts', function($hook) use ($pageHook, $type) {

            if ($hook != $pageHook) return;

            $cm_settings['codeEditor'] = \wp_enqueue_code_editor(['type' => $type]);
            \wp_localize_script('jquery', 'cm_settings', $cm_settings);

            \wp_enqueue_style('wp-codemirror');

        });

        $this->isCodeMirrorEnabled = true;

    }

}
