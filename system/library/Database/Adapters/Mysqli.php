<?php

namespace Copona\Core\System\Library\Database\Adapters;

use Copona\Core\System\Library\Database\DatabaseInterface;

final class Mysqli implements DatabaseInterface
{
    private $connection;

    public function __construct(Array $configs)
    {
        $this->connection = new \mysqli(
            $configs['host'],
            $configs['username'],
            $configs['password'],
            $configs['database'],
            isset($configs['port']) ? $configs['port'] : '3306'
        );

        if ($this->connection->connect_error) {
            throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno);
        }

        $this->connection->set_charset("utf8");
        $this->connection->query("SET SQL_MODE = ''");
    }

    public function query($sql)
    {
        $query = $this->connection->query($sql);

        if (!$this->connection->errno) {
            if ($query instanceof \mysqli_result) {
                $data = array();

                while ($row = $query->fetch_assoc()) {
                    $data[] = $row;
                }

                $result = new \stdClass();
                $result->num_rows = $query->num_rows;
                $result->row = isset($data[0]) ? $data[0] : array();
                $result->rows = $data;

                $query->close();

                return $result;
            } else {
                return true;
            }
        } else {
            throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
        }
    }

    public function escape($value)
    {
        return $this->connection->real_escape_string($value);
    }

    public function countAffected()
    {
        return $this->connection->affected_rows;
    }

    public function getLastId()
    {
        return $this->connection->insert_id;
    }

    public function connected()
    {
        return $this->connected();
    }

    public function connected()
    {
        return $this->connection->ping();
    }

    public function __destruct()
    {
        $this->connection->close();
    }
}