<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway;

use Subway\Util\JsonSerializer;
use Predis\Client;

/**
 * Queue class
 */
class Queue
{
    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var JsonSerializer
     */
    protected $serializer;

    /**
     * Class constructor
     *
     * @param Client $redis
     * @param string $name
     */
    public function __construct(Client $redis, $name)
    {
        $this->redis      = $redis;
        $this->name       = $name;
        $this->serializer = new JsonSerializer();

        $this->create();
    }

    /**
     * Create queue
     */
    public function create()
    {
        $this->redis->sadd('resque:queues', $this->getName());
    }

    /**
     * Clear queue
     */
    public function clear()
    {
        $this->redis->del(sprintf('resque:queue:%s', $this->getName()));
        $this->redis->srem('resque:queues', $this->getName());
    }

    /**
     * Count queue
     *
     * @return int
     */
    public function count()
    {
        return $this->redis->llen(sprintf('resque:queue:%s', $this->getName()));
    }

    /**
     * Get queue name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get all jobs
     *
     * @return string
     */
    public function getJobs()
    {
        return $this->getMessages();
    }

    /**
     * Get all messages
     *
     * @return string
     */
    public function getMessages()
    {
        $messages = $this->redis->lrange(sprintf('resque:queue:%s', $this->getName()), 0, -1) ?: array();
        $data = array();
        foreach ($messages as $message) {
            $data[] = $this->serializer->unserialize($message);
        }

        return $data;
    }

    /**
     * Get next message
     * 
     * @return null|Message
     */
    public function getNextMessage()
    {
        $messages = $this->redis->lrange(sprintf('resque:queue:%s', $this->getName()), 0, 0) ?: array();
        $message  = current($messages);
        if (!$message) {
            return;
        }

        return $this->serializer->unserialize($message);
    }

    /**
     * Pop message
     *
     * @return null|Message
     */
    public function pop()
    {
        $message = $this->redis->lpop(sprintf('resque:queue:%s', $this->getName()));
        if (!$message) {
            return;
        }

        return $this->serializer->unserialize($message);
    }

    /**
     * Put message
     *
     * @param Message $message
     */
    public function put(Message $message)
    {
        $this->redis->rpush(sprintf('resque:queue:%s', $this->getName()), $this->serializer->serialize($message));
    }
}