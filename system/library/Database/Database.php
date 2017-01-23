<?php

namespace Copona\Core\System\Library\Database;

class Database
{
    /**
     * @var DatabaseInterface
     */
    private $adapter;

    public function __construct($adapter, Array $configs)
    {
        if (class_exists($adapter)) {
            $this->adapter = new $adapter($configs);
        } else {
            throw new \Exception('Error: Could not load database adapter ' . $adapter . '!');
        }
    }

    public function query($sql, $params = array())
    {
        return $this->adapter->query($sql, $params);
    }

    public function escape($value)
    {
        return $this->adapter->escape($value);
    }

    public function countAffected()
    {
        return $this->adapter->countAffected();
    }

    public function getLastId()
    {
        return $this->adapter->getLastId();
    }

    public function connected()
    {
        return $this->adapter->connected();
    }
}