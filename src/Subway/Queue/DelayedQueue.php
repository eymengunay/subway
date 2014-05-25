<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Queue;

use Subway\Queue;
use Subway\Message;
use Predis\Client;

/**
 * Delayed queue class
 */
class DelayedQueue extends Queue
{
    /**
     * Class constructor
     *
     * @param Client $redis
     * @param string $name
     */
    public function __construct(Client $redis, $name = 'delayed')
    {
        parent::__construct($redis, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $timestamps = $this->redis->zrangebyscore(sprintf('resque:%s_queue_schedule', $this->getName()), '-inf', 'inf');
        foreach ($timestamps as $timestamp) {
            $this->redis->del(sprintf('resque:%s:%s', $this->getName(), $timestamp));    
        }
        $this->redis->del(sprintf('resque:%s_queue_schedule', $this->getName()));
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $count = 0;
        $timestamps = $this->redis->zrangebyscore(sprintf('resque:%s_queue_schedule', $this->getName()), '-inf', 'inf');
        foreach ($timestamps as $timestamp) {
            $count += $this->redis->llen(sprintf('resque:%s:%s', $this->getName(), $timestamp));
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getJobs()
    {
        $jobs = array();
        $timestamps = $this->redis->zrangebyscore(sprintf('resque:%s_queue_schedule', $this->getName()), '-inf', 'inf');
        foreach ($timestamps as $timestamp) {
            $items = $this->redis->lrange(sprintf('resque:queue:%s', $this->getName()), 0, -1) ?: array();
            foreach ($items as $item) {
                $jobs[] = Message::jsonUnserialize($item);
            }
        }

        return $jobs;
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        $timestamps = $this->redis->zrangebyscore(sprintf('resque:%s_queue_schedule', $this->getName()), '-inf', time(), array('limit' => array(0, 1)));
        if (empty($timestamps)) {
            return null;
        }

        $key = sprintf('resque:%s:%s', $this->getName(), $timestamps[0]);
        $item = $this->redis->lpop($key);

        if (is_null($item) || $this->redis->llen($key) == 0) {
            $this->redis->del($key);
            $this->redis->zrem(sprintf('resque:%s_queue_schedule', $this->getName()), $timestamps[0]);
        }

        return $item ? Message::jsonUnserialize($item) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function put(Message $message)
    {
        $at = $message->getAt()->format('U');
        $this->redis->rpush(sprintf('resque:%s:%s', $this->getName(), $at), json_encode($message));
        $this->redis->zadd(sprintf('resque:%s_queue_schedule', $this->getName()), $at, $at);
    }
}