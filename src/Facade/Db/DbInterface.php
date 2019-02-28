<?php

namespace Broneq\SqlStreamQueue\Facade\Db;

/**
 * Interface DbInterface
 */
interface DbInterface
{
    /**
     * Prepares query
     * @param string $sql
     * @return mixed
     */
    public function prepare($sql);

    /**
     * Executes query
     * @param array $params
     * @return bool
     */
    public function execute($params = []);

    /**
     * Returns an array containing all of the result set rows
     * @return mixed
     */
    public function fetchAll();
}