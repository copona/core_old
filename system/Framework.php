<?php

namespace Copona\Core\System;

use Copona\Core\System\Engine\Action;
use Copona\Core\System\Engine\Event;
use Copona\Core\System\Engine\Front;
use Copona\Core\System\Engine\Loader;
use Copona\Core\System\Engine\Registry;
use Copona\Core\System\Library\Cache\Cache;
use Copona\Core\System\Library\Config\Config;
use Copona\Core\System\Library\Database\Database;
use Copona\Core\System\Library\Document;
use Copona\Core\System\Library\Language;
use Copona\Core\System\Library\Request;
use Copona\Core\System\Library\Response;
use Copona\Core\System\Library\SeoUrl;
use Copona\Core\System\Library\Session\Session;
use Copona\Core\System\Library\Url;

class Framework
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * admin or catalog
     * @var string
     */
    private $application_env;

    public function __construct()
    {
        // Registry
        $this->registry = new Registry();

        // Config
        $this->registry->singleton('config', function () {
            return new Config([DIR_ROOT . '/config']);
        });

        $config = $this->registry->get('config');

        //Define application env
        if (strpos($_SERVER['REQUEST_URI'], '/' . $config->get('admin.admin_uri', '/admin')) === false) {
            $this->setApplicationEnv('catalog');
        } else {
            $this->setApplicationEnv('admin');
        }

        $this->defineConstants();
    }

    public function boot()
    {
        // Check Version
        if (version_compare(phpversion(), '5.4.0', '<') == true) {
            exit('PHP5.4+ Required');
        }

        // Magic Quotes Fix
        if (ini_get('magic_quotes_gpc')) {

            function clean($data)
            {
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        $data[clean($key)] = clean($value);
                    }
                } else {
                    $data = stripslashes($data);
                }

                return $data;
            }

            $_GET = clean($_GET);
            $_POST = clean($_POST);
            $_COOKIE = clean($_COOKIE);
        }

        if (!ini_get('date.timezone')) {
            date_default_timezone_set('UTC');
        }

        // Windows IIS Compatibility
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            if (isset($_SERVER['SCRIPT_FILENAME'])) {
                $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
            }
        }

        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            if (isset($_SERVER['PATH_TRANSLATED'])) {
                $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
            }
        }

        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);

            if (isset($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
        }

        // Check if SSL
        if ((isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) || $_SERVER['SERVER_PORT'] == 443) {
            $_SERVER['HTTPS'] = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $_SERVER['HTTPS'] = true;
        } else {
            $_SERVER['HTTPS'] = false;
        }

        // Universal Host redirect to correct hostname
        if ($_SERVER['HTTP_HOST'] != parse_url(HTTP_SERVER)['host'] && $_SERVER['HTTP_HOST'] != parse_url(HTTP_SERVER)['host']) {
            header("Location: " . ($_SERVER['HTTPS'] ? HTTPS_SERVER : HTTP_SERVER) . ltrim('/', $_SERVER['REQUEST_URI']));
        }

        // Modification Override
        function modification($filename)
        {
            if (defined('DIR_CATALOG')) {
                $file = DIR_MODIFICATION . 'admin/' . substr($filename, strlen(DIR_APPLICATION));
            } elseif (defined('DIR_OPENCART')) {
                $file = DIR_MODIFICATION . 'install/' . substr($filename, strlen(DIR_APPLICATION));
            } else {
                $file = DIR_MODIFICATION . 'catalog/' . substr($filename, strlen(DIR_APPLICATION));
            }

            if (substr($filename, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
                $file = DIR_MODIFICATION . 'system/' . substr($filename, strlen(DIR_SYSTEM));
            }

            if (is_file($file)) {
                return $file;
            }

            return $filename;
        }

        // Engine
        //require_once(modification(DIR_SYSTEM . 'engine/action.php'));
        //require_once(modification(DIR_SYSTEM . 'engine/controller.php'));
        //require_once(modification(DIR_SYSTEM . 'engine/event.php'));
        //require_once(modification(DIR_SYSTEM . 'engine/front.php'));
        //require_once(modification(DIR_SYSTEM . 'engine/loader.php'));
        //require_once(modification(DIR_SYSTEM . 'engine/model.php'));
        //require_once(modification(DIR_SYSTEM . 'engine/registry.php'));
        //require_once(modification(DIR_SYSTEM . 'engine/proxy.php'));

        // Helper
        require_once(DIR_SYSTEM . '/helper/general.php');
        require_once(DIR_SYSTEM . '/helper/utf8.php');
        require_once(DIR_SYSTEM . '/helper/json.php');
        require_once(DIR_SYSTEM . '/helper/debug.php'); //@TODO check before is DEV mode
    }

    /**
     * @throws \Exception
     */
    public function start()
    {
        /** @var Config $config */
        $config = $this->registry->get('config');

        // Event
        $event = new Event($this->registry);
        $this->registry->set('event', $event);

        // Event Register
        if ($config->has('action.action_event')) {
            foreach ($config->get('action.action_event') as $key => $value) {
                $event->register($key, new Action($value));
            }
        }

        // Loader
        $loader = new Loader($this->registry);
        $this->registry->set('load', $loader);

        // Request
        $this->registry->set('request', new Request());

        // Database
        if ($config->get('database.db_autostart')) {

            $connection_default = $config->get('database.default', false);

            if ($connection_default && $config->has('database.connections.' . $connection_default)) {

                $this->registry->singleton('db', function () use ($config, $connection_default) {

                    $db_configs = $config->get('database.connections.' . $connection_default);

                    return new Database($config->get('database.adapter'), $db_configs);
                });

                if (!$this->registry->get('db')->connected()) {
                    throw new \Exception('Check Config file for correct Database connection!');
                }

            } else {
                throw new \Exception('Check Config file for correct Database connection!');
            }
        }

        // Session
        $session = new Session($config->get('session.driver', Session\Native::class));

        if ($config->get('session.session_autostart')) {
            $session->start();
        }

        $this->registry->set('session', $session);

        // Cache
        $this->registry->set('cache', new Cache($config->get('cache_type'), $config->get('cache_expire')));

        // Url
        if ($config->get('general.url_autostart')) {
            $this->registry->set('url', new Url($config->get('site_base'), $config->get('site_ssl')));
        }

        // Copona seo urls
        if ($config->get('general.url_autostart')) {
            $this->registry->set('seourl', new SeoUrl($this->registry));
        }

        // Language
        $language = new Language($config->get('general.language_default'), $this->registry);
        $language->load($config->get('general.language_default'));
        $this->registry->set('language', $language);

        // Document
        $this->registry->set('document', new Document());

        // Config Autoload
        foreach ($config->get('general.config_autoload', []) as $value) {
            $loader->config($value);
        }

        // Language Autoload
        foreach ($config->get('general.language_autoload', []) as $value) {
            $loader->language($value);
        }

        // Library Autoload
        foreach ($config->get('general.library_autoload', []) as $value) {
            $loader->library($value);
        }

        // Model Autoload
        foreach ($config->get('general.model_autoload', []) as $value) {
            $loader->model($value);
        }
    }

    private function defineConstants()
    {
        /** @var Config $config */
        $config = $this->registry->get('config');

        define('DIR_CORE', realpath(DIR_ROOT . '/core'));

        // HTTP
        define('HTTP_SERVER', 'http://default2/');

        // HTTPS
        define('HTTPS_SERVER', 'http://default2/');

        // DIR
        define('DIR_APPLICATION', realpath(DIR_CORE . '/catalog/'));
        define('DIR_SYSTEM', realpath(DIR_CORE . '/system/'));
        define('DIR_LANGUAGE', realpath(DIR_CORE . '/catalog/language/'));
        define('DIR_TEMPLATE', '/app/themes/');
        define('DIR_CONFIG', realpath(DIR_CORE . '/system/config/'));

        // Storage
        define('DIR_STORAGE', $config->getPathByConfig('general.storage.storage_path'));
        define('DIR_IMAGE', $config->getPathByConfig('general.storage.image_path'));
        define('DIR_CACHE', $config->getPathByConfig('cache.storage.cache_path'));
        define('DIR_DOWNLOAD', $config->getPathByConfig('general.storage.download_path'));
        define('DIR_LOGS', $config->getPathByConfig('general.storage.log_path'));
        define('DIR_MODIFICATION', $config->getPathByConfig('general.storage.modification_path'));
        define('DIR_UPLOAD', $config->getPathByConfig('general.storage.upload_path'));
    }

    public function output()
    {
        // Response
        $response = new Response();
        $response->addHeader('Content-Type: text/html; charset=utf-8');
        $this->registry->set('response', $response);

        // Front Controller
        $controller = new Front($this->registry);

        // Pre Actions
        foreach ($this->registry->get('config')->get('action.action_pre_action', []) as $value) {
            $controller->addPreAction(new Action($value));
        }

        // Dispatch
        $controller->dispatch(
            new Action($this->registry->get('config')->get('action.action_router')),
            new Action($this->registry->get('config')->get('action.action_error'))
        );

        // Output
        $response->setCompression($this->registry->get('config')->get('general.config_compression'));
        $response->output();
    }

    public function setApplicationEnv($application_env)
    {
        $this->application_env = $application_env;
    }

    public function getApplicationEnv()
    {
        return $this->application_env;
    }
}