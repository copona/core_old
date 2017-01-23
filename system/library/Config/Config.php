<?php

namespace Copona\Core\System\Library\Config;

class Config extends \Noodlehaus\Config
{
    public function __construct($config_path)
    {
        parent::__construct($config_path);
    }

    public static function load($name)
    {
        $file = DIR_CONFIG . $name . '.php';
        parent::load($file);
    }

    /**
     * Get complete path by config value
     * @param $key
     * @return bool|string
     */
    public function getPathByConfig($key)
    {
        $key = $this->get($key);

        if ($key && is_dir(realpath(DIR_ROOT . '/' . $key))) {
            return realpath(DIR_ROOT . '/' . $key);
        }

        return false;
    }
}