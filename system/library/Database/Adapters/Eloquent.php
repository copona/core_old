<?php

namespace Copona\Core\System\Library\Database\Adapters;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Copona\Core\System\Library\Database\DatabaseInterface;

class Eloquent implements DatabaseInterface
{
    /**
     * @var Capsule
     */
    private $capsule;

    /**
     * Connection name
     * @var string
     */
    private $name = 'default';

    /**
     * @var \Illuminate\Database\Connection
     */
    private $connection;

    /**
     * @var int
     */
    private $countAffected = 0;

    /**
     * Eloquent constructor.
     * @param array $configs
     */
    public function __construct(Array $configs)
    {
        $this->name = isset($configs['name']) ? $configs['name'] : 'default';

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => isset($configs['driver']) ? $configs['driver'] : 'mysql',
            'host'      => $configs['host'],
            'database'  => $configs['database'],
            'username'  => $configs['username'],
            'password'  => $configs['password'],
            'charset'   => isset($configs['charset']) ? $configs['charset'] : 'utf8',
            'collation' => isset($configs['collation']) ? $configs['collation'] : 'utf8_unicode_ci',
            'prefix'    => isset($configs['prefix']) ? $configs['prefix'] : NULL,
            'port'      => isset($configs['port']) ? $configs['port'] : '3306'
        ], $this->name);

        $capsule->setEventDispatcher(new Dispatcher(new Container));

        $capsule->setAsGlobal();

        $capsule->bootEloquent();
        $this->capsule = $capsule;
        $this->connection = $this->capsule->schema($this->name)->getConnection();
    }

    /**
     * @param $sql
     * @return bool|int|\stdClass
     */
    public function query($sql)
    {
        if ($this->checkIsSelect($sql)) {
            $return = $this->connection->getPdo()->query($sql);
            $data = $return->fetchAll();
            $result = new \stdClass();
            $result->num_rows = $return->rowCount();
            $result->row = isset($data[0]) ? $data[0] : array();
            $result->rows = $data;
            $this->countAffected = $return->rowCount();
        } else {
            $result = $this->execute($sql);
            $this->countAffected = $result;
            $result = (bool)$result;
        }

        return $result;
    }

    /**
     * @param $sql
     * @return int
     */
    public function execute($sql)
    {
        return $this->connection->affectingStatement($sql);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function escape($value)
    {
        return $value;

        return $this->connection->quote($value);
    }

    /**
     * @return int
     */
    public function countAffected()
    {
        return $this->countAffected;
    }

    /**
     * @return string
     */
    public function getLastId()
    {
        return $this->connection->getPdo()->lastInsertId();
    }

    /**
     * @param string $name
     * @return \Illuminate\Database\Connection
     */
    public function getConnection($name = 'default')
    {
        return $this->capsule->getConnection($name);
    }

    /**
     * @param $sql
     * @return bool true to select query
     */
    private function checkIsSelect($sql)
    {
        return (substr(strtoupper(trim($sql)), 0, 6) == 'SELECT');
    }

    /**
     * @return bool
     */
    public function connected()
    {
        if ($this->getConnection()->getDatabaseName()) {
            return true;
        } else {
            return false;
        }
    }
}