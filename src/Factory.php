<?php


namespace Broneq\SqlStreamQueue;

use Broneq\SqlStreamQueue\Facade\Db\DbInterface;
use Broneq\SqlStreamQueue\Serializer\SerializerInterface;

/**
 * Class Factory
 */
class Factory
{
    private $db;
    private $serializer;
    private $stream;

    /**
     * Factory constructor.
     * @param DbInterface         $db
     * @param SerializerInterface $serializer
     */
    public function __construct(DbInterface $db, SerializerInterface $serializer)
    {
        $this->db = $db;
        $this->serializer = $serializer;
        $this->stream = new Stream($db, $serializer);
    }

    /**
     * Get stream
     * @return Stream
     */
    public function getStream()
    {
        return $this->stream;
    }

    public function getConsumer()
    {

    }
}