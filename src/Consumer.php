<?php


namespace Broneq\SqlStreamQueue;

/**
 * Class Consumer
 */
class Consumer
{
    private $loop;
    /** @var null|callable */
    private $callbackFunction = null;
    protected $streamName;
    protected $consumerGroup;
    /** @var Stream */
    protected $stream;

    /**
     * Consumer constructor.
     * @param string $streamName
     * @param string $consumerGroup
     * @param Stream $stream
     * @param int    $interval
     */
    public function __construct($streamName, $consumerGroup, $stream, $interval = 5)
    {
        $this->streamName = $streamName;
        $this->consumerGroup = $consumerGroup;
        $this->stream = $stream;
        $this->loop = \React\EventLoop\Factory::create();
        $this->loop->addPeriodicTimer($interval, [$this , 'readStream']);
        //todo register signals

    }

    public function run() {
        $this->loop->run();
    }

    /**
     * You can extend this class and use custom way processing single message
     * @param int   $id
     * @param mixed $data
     */
    public function onMessage($id, $data)
    {

    }

    /**
     * You can simply register callback function - it will by automatically ACK'd after proccessing
     * @param callable $function
     */
    public function registerCallbackFunction(callable $function)
    {
        $this->callbackFunction = $function;
    }

    /**
     * Read stream
     */
    public function readStream()
    {
        $data = $this->stream->xRead($this->streamName, $this->consumerGroup);
        foreach ($data as $id => $message) {
            $this->processStreamMessage($id, $message);
        }
    }

    /**
     * Process stream message by callback function or internal class method
     * @param int   $id
     * @param mixed $message
     */
    private function processStreamMessage($id, $message)
    {
        if (is_callable($this->callbackFunction)) {
            $callable = $this->callbackFunction;
            $callable($id, $message);
            $this->stream->xAck($this->consumerGroup, $id);
        } else {
            $this->onMessage($id, $message);
        }
    }
}