<?php


namespace Broneq\SqlStreamQueue\Serializer;

/**
 * Interface SerializerInterface
 */
interface SerializerInterface
{
    /**
     * Serialize data
     * @param mixed $data
     * @return false|string
     */
    public function serialize($data);

    /**
     * Deserialize data
     * @param string $data
     * @return mixed
     */
    public function deserialize($data);
}