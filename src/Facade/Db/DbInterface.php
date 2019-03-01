<?php

namespace Broneq\SqlStreamQueue\Facade\Db;

/**
 * Interface DbInterface
 */
interface DbInterface
{
    /**
     * Executes query
     * @param string $sql
     * @param array  $params
     * @return bool
     */
    public function query($sql, $params = []);

    /**
     * Returns an array containing all of the result set rows
     * @return mixed
     */
    public function fetchAll();

    /**
     * Returns last inserted id
     * @return mixed
     */
    public function lastInsertId();
}