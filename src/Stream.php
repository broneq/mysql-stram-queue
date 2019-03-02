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
     * Create new stream
     * @param string $streamName
     * @return mixed
     */
    public function xCreateStream($streamName)
    {
        $this->db->query('INSERT INTO ssq_stream (name) VALUES (:stream_name:)', [':stream_name:' => $streamName]);
        return $this->db->lastInsertId();
    }

    /**
     * Create consumer group for stream
     * @param $streamName
     * @param $consumerGroup
     * @return mixed
     */
    public function xCreateGroup($streamName, $consumerGroup)
    {
        $this->db->query('START TRANSACTION');
        $this->db->query('INSERT INTO ssq_consumer_group  (stream_id, name) SELECT id, :consumerGroup: FROM ssq_stream s WHERE s.name=:streamName:',
                         [
                             ':consumerGroup:' => $consumerGroup,
                             ':streamName:' => $streamName,
                         ]);

        $consumerGroupId = $this->db->lastInsertId();

        $this->db->query('INSERT INTO ssq_status (stream_queue_id,consumer_group_id,status,created_at) 
             SELECT s.id,:consumer_group_id:,:status:,now() FROM ssq_stream_queue sq JOIN ssq_stream s ON (s.id=sq.stream_id) WHERE s.name=:streamName:',
                         [
                             ':streamName:' => $streamName,
                             ':consumer_group_id:' => $consumerGroupId,
                             ':status:' => self::STATUS_NEW
                         ]);

        $this->db->query('COMMIT');
        return $this->db->lastInsertId();
    }

    /**
     * Add message to stream
     * @param string      $streamName
     * @param mixed       $message
     * @param string|null $plannedExecution
     * @return bool
     */
    public function xAdd($streamName, $message, $plannedExecution = null)
    {
        $this->db->query('INSERT INTO ssq_stream_queue (stream_id, message, created_at, planned_execution)
        SELECT id,:message:, now(), :planned_execution: FROM ssq_stream s WHERE name=:stream_name:',
                         [
                             ':stream_name:' => $streamName,
                             ':message:' => $this->serializer->serialize($message),
                             ':planned_execution:' => $plannedExecution,
                         ]);
        $streamQueueId = $this->db->lastInsertId();
        return $this->db->query('INSERT INTO ssq_status (stream_queue_id,consumer_group_id,status,created_at) 
            SELECT :stream_queue_id:,g.id,:status:,now() FROM ssq_consumer_group g JOIN ssq_stream_queue sq ON (sq.stream_id=g.stream_id) 
            WHERE sq.id=:stream_queue_id:',
                                [
                                    ':stream_queue_id:' => $streamQueueId,
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
        return $this->db->query('DELETE st,sq FROM ssq_stream_queue sq JOIN ssq_status st ON (sq.id=st.stream_queue_id AND sq.id=:id:)',
                                [
                                    ':id:' => $id
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
        $this->db->query('UPDATE ssq_status st SET st.status=:ack: JOIN ssq_stream s ON (s.id=st.stream_id) JOIN ssq_consumer_group sg ON (sg.id=st.consumer_group_id)
            WHERE sg.name=:consumerGroup: AND s.id=:id:',
                         [
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
        $this->db->query('START TRANSACTION');
        $result = $this->db->query('SELECT sq.id,st.id as status_id,sq.message FROM ssq_stream_queue sq 
            JOIN ssq_status st ON (sq.id=st.stream_queue_id)
            JOIN ssq_stream s ON (s.id=sq.stream_id)  
            JOIN ssq_consumer_group sg ON (sg.id=st.consumer_group_id)
            WHERE sg.name=:consumerGroup: AND s.name=:streamName: AND st.status=:status: LIMIT :limit: FOR UPDATE',
                                   [
                                       ':consumerGroup:' => $consumerGroup,
                                       ':streamName:' => $streamName,
                                       ':status:' => self::STATUS_NEW,
                                       ':limit:' => $count,
                                   ]);

        $pendingIds = [];
        $data = [];
        foreach ($this->db->resultToArray($result) as $item) {
            $pendingIds[] = $item['status_id'];
            $data[$item['id']] = $this->serializer->deserialize($item['message']);
        }
        if ($pendingIds) {
            $result = $this->db->query('UPDATE ssq_status st SET st.status=:status: WHERE st.id IN (' . implode(',', $pendingIds) . ')',
                                       [
                                           ':status:' => self::STATUS_PENDING,
                                       ]);
        }
        $this->db->query('COMMIT');
        echo $this->db->lastQuery();
        //        $this->db->query('COMMIT');
        return $data;
        //        return $this->xPending($streamName, $consumerGroup, $count);
    }

    /**
     * Read pending messages from stream
     * @param string $streamName
     * @param string $consumerGroup
     * @param int    $count
     * @return array|null
     */
    public function xPending($streamName, $consumerGroup, $count = 1)
    {
        $result = $this->db->query('SELECT sq.id,st.id as status_id,sq.message FROM ssq_stream_queue sq 
            JOIN ssq_status st ON (sq.id=st.stream_queue_id)
            JOIN ssq_stream s ON (s.id=sq.stream_id)  
            JOIN ssq_consumer_group sg ON (sg.id=st.consumer_group_id)
            WHERE sg.name=:consumerGroup: AND s.name=:streamName: AND st.status=:status: LIMIT :limit: FOR UPDATE',
                                   [
                                       ':consumerGroup:' => $consumerGroup,
                                       ':streamName:' => $streamName,
                                       ':status:' => self::STATUS_PENDING,
                                       ':limit:' => $count,
                                   ]);
        $data = [];
        foreach ($this->db->resultToArray($result) as $item) {
            $data[$item['id']] = $this->serializer->deserialize($item['message']);
        }
        return $data ? $data : null;
    }
}