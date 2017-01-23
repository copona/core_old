<?php

namespace Copona\Core\System\Library\Database\Adapters;

use Copona\Core\System\Library\Database\DatabaseInterface;

class Mysql implements DatabaseInterface
{
    private $connection;

    public function __construct(Array $configs)
    {
        $port = isset($configs['port']) ? $configs['port'] : '3306';

        if (!$this->connection = mysql_connect($configs['host'] . ':' . $port, $configs['username'], $configs['password'])) {
            trigger_error('Error: Could not make a database link using ' . $configs['username'] . '@' . $configs['host']);
            exit();
        }

        if (!mysql_select_db($configs['database'], $this->connection)) {
            throw new \Exception('Error: Could not connect to database ' . $configs['database']);
        }

        mysql_query("SET NAMES 'utf8'", $this->connection);
        mysql_query("SET CHARACTER SET utf8", $this->connection);
        mysql_query("SET CHARACTER_SET_CONNECTION=utf8", $this->connection);
        mysql_query("SET SQL_MODE = ''", $this->connection);
    }

    public function query($sql)
    {
        if ($this->connection) {
            $resource = mysql_query($sql, $this->connection);

            if ($resource) {
                if (is_resource($resource)) {
                    $i = 0;

                    $data = array();

                    while ($result = mysql_fetch_assoc($resource)) {
                        $data[$i] = $result;

                        $i++;
                    }

                    mysql_free_result($resource);

                    $query = new \stdClass();
                    $query->row = isset($data[0]) ? $data[0] : array();
                    $query->rows = $data;
                    $query->num_rows = $i;

                    unset($data);

                    return $query;
                } else {
                    return true;
                }
            } else {
                $trace = debug_backtrace();

                throw new \Exception('Error: ' . mysql_error($this->connection) . '<br />Error No: ' . mysql_errno($this->connection) . '<br /> Error in: <b>' . $trace[1]['file'] . '</b> line <b>' . $trace[1]['line'] . '</b><br />' . $sql);
            }
        }
    }

    public function escape($value)
    {
        if ($this->connection) {
            return mysql_real_escape_string($value, $this->connection);
        }
    }

    public function countAffected()
    {
        if ($this->connection) {
            return mysql_affected_rows($this->connection);
        }
    }

    public function getLastId()
    {
        if ($this->connection) {
            return mysql_insert_id($this->connection);
        }
    }

    public function connected()
    {
        if ($this->connection) {
            return true;
        } else {
            return false;
        }
    }

    public function __destruct()
    {
        if ($this->connection) {
            mysql_close($this->connection);
        }
    }

}