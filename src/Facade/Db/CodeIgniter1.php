<?php


namespace Broneq\SqlStreamQueue\Facade\Db;


class CodeIgniter1 implements DbInterface
{
    private $db;

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
        return $this->db->query($this->bindParams($sql, $params));
    }

    public function lastQuery() {
        return $this->db->last_query();
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
    public function resultToArray($result)
    {
        return is_object($result) ? $result->result_array() : [];
    }

    /**
     * @inheritdoc
     */
    public function lastInsertId()
    {
        return $this->db->insert_id();
    }


}