<?php


namespace Broneq\SqlStreamQueue\Serializer;

/**
 * Class Json
 */
class Json implements SerializerInterface
{
    /**
     * @inheritdoc
     */
    public function serialize($data)
    {
        return \json_encode($data);
    }

    /**
     * @inheritdoc
     */
    public function deserialize($data)
    {
        return \json_decode($data, true);
    }
}