<?php

namespace Copona\Core\System\Engine;

use \Illuminate\Container\Container;

class Registry extends Container
{
    public function get($key)
    {
        return $this->make($key);
    }

    public function set($key, $value)
    {
        $this->bind($key, $value);
    }
}