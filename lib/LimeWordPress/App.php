<?php

namespace LimeWordPress;

use \Lime\Response;

class App extends \LimeExtra\App {

    public function __construct ($settings = []) {

        $settings['session.init'] = isset($settings['session.init']) ? $settings['session.init'] : false;

        $settings['helpers'] = \array_merge([
            'settings' => 'LimeWordPress\\Helper\\Settings',
            'wp'       => 'LimeWordPress\\Helper\\WP',
        ], $settings['helpers'] ?? []);

        parent::__construct($settings);

    }

    /**
    * Run Application
    * @param  String $route Route to parse
    * @return void
    */
    public function run($route = null, $request = null, $flush = true) {

        // simple output buffer to allow session calls in shortcodes
        // otherwise headers would be already sent
        \ob_start();

        $self = $this;

        if ($route) {
            $this->registry['route'] = $route;
        }

        if ($request) {
            $this->request = $request;
        }

        if (!isset($this->request)) {
            $this->request = $this->getRequestfromGlobals();
        }

        \register_shutdown_function(function() use($self){
            \session_write_close();
            $self->trigger('shutdown');
        });

        $this->request->route = $this->registry['route'];

        $this->response = new Response();
        $this->trigger('before');
        $this->response->body = $this->dispatch($this->registry['route']);

        // let WP handle the 404 page
//         if ($this->response->body === false) {
        if ($this->response->body === false && COCKPIT_ADMIN_WP) {
            $this->response->status = 404;
        }

        $this->trigger('after');

        if ($flush) {
            $this->response->flush();
        }

//         return $this->response;

        if (\ob_get_length()) $this->stop();

    }

    public function loadModules($dirs, $autoload = true, $prefix = false) {

        $modules  = [];
        $dirs     = (array)$dirs;
        $disabled = $this->registry['modules.disabled'] ?? null;

        // enable modules explicitely
//         $enabled = \get_option($this->helper('settings')->prefix.'modules_enabled', []);

        $settingsModules = [];

        foreach ($dirs as &$dir) {

            if (\file_exists($dir)){

                $pfx = \is_bool($prefix) ? \strtolower(basename($dir)) : $prefix;

                // load modules
                foreach (new \DirectoryIterator($dir) as $module) {

                    if ($module->isFile() || $module->isDot()) continue;

                    $name = $prefix ? "{$pfx}-".$module->getBasename() : $module->getBasename();

                    if ($disabled && \in_array($name, $disabled)) continue;

                    $settingsModules[$name] = [];

//                     if (!($enabled[strtolower($name)] ?? false)) continue;

                    $this->registerModule($name, $module->getRealPath());

                    $modules[] = \strtolower($module);
                }

                if ($autoload) $this['autoload']->append($dir);
            }
        }

        // let the settings helper know about available modules
        $this->helper('settings')->modules = array_merge($this->helper('settings')->modules, $settingsModules);

        return $modules;
    }

}
