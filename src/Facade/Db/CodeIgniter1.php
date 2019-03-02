<?php


namespace Broneq\SqlStreamQueue\Facade\Db;


class CodeIgniter1 implements DbInterface
{
    private $db;
    private $result;

    /**
     * CodeIgniter1 constructor.
     * @param mixed $db
     */
    public function __construct($db)
    {
        $this->db = $db;
        if (!$this->db->conn_id) {
            $this->db->initialize();
        }
    }

    /**
     * @inheritdoc
     */
    public function query($sql, $params = [])
    {
        $this->result = $this->db->query($this->bindParams($sql, $params));
    }

    /**
     * Bind params
     * @param string $sql
     * @param array  $params
     * @return string
     */
    private function bindParams($sql, $params)
    {
        foreach ($params as $name => $value) {
            $sql = str_replace($name, $this->db->escape($value), $sql);
        }
        return $sql;
    }

    /**
     * @inheritdoc
     */
    public function fetchAll()
    {
        return $this->result ? $this->result : [];
    }

    /**
     * @inheritdoc
     */
    public function lastInsertId()
    {
        return $this->db->insert_id();
    }


}