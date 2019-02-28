<?php


namespace Broneq\SqlStreamQueue;

use Broneq\SqlStreamQueue\Facade\Db\DbInterface;
use Broneq\SqlStreamQueue\Serializer\SerializerInterface;

/**
 * Class Stream
 */
class Stream
{
    private $db;
    private $serializer;

    /**
     * Stream constructor.
     * @param DbInterface         $db
     * @param SerializerInterface $serializer
     */
    public function __construct(DbInterface $db, SerializerInterface $serializer)
    {
        $this->db = $db;
        $this->serializer = $serializer;
    }

    /**
     * Put mressage to stream
     * @param string      $streamName
     * @param mixed       $message
     * @param string|null $plannedExecution
     * @return bool
     */
    public function xPut($streamName, $message, $plannedExecution = null)
    {
        
    }

    /**
     * Delete message from stream
     * @param string $stramName
     * @param int    $id
     * @return bool
     */
    public function xDel($stramName, $id)
    {

    }

    /**
     * Acknowledge message
     * @param string $streamName
     * @param string $consumerGroup
     * @param int    $id
     * @return bool
     */
    public function xAck($streamName, $consumerGroup, $id)
    {

    }

    /**
     * Read new messages from stream
     * @param string $streamName
     * @param string $consumerGroup
     * @param int    $count
     * @return array|null
     */
    public function xRead($streamName, $consumerGroup, $count = 1)
    {

    }

    /**
     * Read pending messages from stream
     * @param     $streamName
     * @param     $consumerGroup
     * @param int $count
     * @return array|null
     */
    public function xPending($streamName, $consumerGroup, $count = 1)
    {

    }
}