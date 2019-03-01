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

    const STATUS_NEW = 'NEW';
    const STATUS_PENDING = 'PENDING';
    const STATUS_ACK = 'ACK';

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
     * Create consumer group for stream
     * @param $streamName
     * @param $consumerGroup
     * @return mixed
     */
    public function xCreateGroup($streamName, $consumerGroup)
    {
        $this->db
            ->prepare('INSERT INTO ssq_consumer_group  (stream_id, name) SELECT id, :consumerGroup: FROM ssq_stream s WHERE s.stream_name=:streamName:')
            ->execute([
                ':consumerGroup:' => $consumerGroup,
                ':streamName:' => $streamName,
            ]);
        return $this->db->lastInsertId();
    }

    /**
     * Put mressage to stream
     * @param string      $streamName
     * @param mixed       $message
     * @param string|null $plannedExecution
     * @return bool
     */
    public function xAdd($streamName, $message, $plannedExecution = null)
    {
        $this->db
            ->prepare('INSERT INTO ssq_stream (stream_name, message, created_at, planned_execution) VALUES (:stream_name:, :message:, now(), :planned_execution:)')
            ->execute([
                ':stream_name:' => $streamName,
                ':message:' => $this->serializer->serialize($message),
                ':planned_execution:' => $plannedExecution,
            ]);
        $streamId = $this->db->lastInsertId();
        return $this->db
            ->prepare('INSERT INTO ssq_status (stream_id,consumer_group_id,status,created_at) 
            SELECT :stream_id:,g.id,:status:,now() FROM ssq_consumer_group g WHERE g.stream_id=:stream:id:')
            ->execute([
                ':stream_id:' => $streamId,
                ':status:' => self::STATUS_NEW
            ]);
    }

    /**
     * Delete message from stream
     * @param int $id
     * @return bool
     */
    public function xDel($id)
    {
        return $this->db
            ->prepare('UPDATE ssq_stream s,ssq_status st WHERE s.id=st.stream_id AND s.id=:id:')
            ->execute([
                'id' => $id
            ]);
    }

    /**
     * Acknowledge message
     * @param string $consumerGroup
     * @param int    $id
     * @return void
     */
    public function xAck($consumerGroup, $id)
    {
        $this->db
            ->prepare('UPDATE ssq_status st SET st.status=:ack: JOIN ssq_stream s ON (s.id=st.stream_id) JOIN ssq_consumer_group sg ON (sg.id=st.consumer_group_id)
            WHERE sg.name=:consumerGroup: AND s.id=:id:')
            ->execute([
                ':consumerGroup:' => $consumerGroup,
                ':id:' => $id,
                ':ack:' => self::STATUS_ACK,
            ]);
    }

    /**
     * Read new messages from stream
     * @param string $streamName
     * @param string $consumerGroup
     * @param int    $count
     * @return mixed
     */
    public function xRead($streamName, $consumerGroup, $count = 1)
    {
        $this->db
            ->prepare('UPDATE ssq_status st SET st.status=:pending: JOIN ssq_stream s ON (s.id=st.stream_id) JOIN ssq_consumer_group sg ON (sg.id=st.consumer_group_id)
            WHERE sg.name=:consumerGroup: AND s.stream_name=:streamName: AND st.status=:status: LIMIT :limit:')
            ->execute([
                ':consumerGroup:' => $consumerGroup,
                ':streamName:' => $streamName,
                ':status:' => self::STATUS_NEW,
                ':pending' => self::STATUS_PENDING,
                ':limit:' => $count,
            ]);

        return $this->xPending($streamName, $consumerGroup, $count);
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
        $this->db
            ->prepare('SELECT s.id,s.message FROM ssq_stream s JOIN ssq_status st ON (s.id=st.stream_id) JOIN ssq_consumer_group sg ON (sg.id=st.consumer_group_id)
            WHERE sg.name=:consumerGroup: AND s.stream_name=:streamName: AND st.status=:status: LIMIT :limit:')
            ->execute([
                ':consumerGroup:' => $consumerGroup,
                ':streamName:' => $streamName,
                ':status:' => self::STATUS_PENDING,
                ':limit:' => $count,
            ]);
        $data = [];
        foreach ($this->db->fetchAll() as $item) {
            $data[$item['id']] = $this->serializer->deserialize($item['message']);
        }
        return $data ? $data : null;
    }
}