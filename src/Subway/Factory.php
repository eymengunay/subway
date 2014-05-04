<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway;

use Subway\Events;
use Subway\Queue;
use Subway\Job;
use Subway\Queue\DelayedQueue;
use Subway\Queue\RepeatingQueue;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Factory class
 */
class Factory
{
    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Class constructor
     *
     * @param Client $redis
     */
    public function __construct(Client $redis)
    {
        $this->redis      = $redis;
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Clear everything
     */
    public function clear()
    {
        foreach ($this->getQueues() as $queue) {
            $this->getQueue($queue->getName())->clear();
        }
        $this->getDelayedQueue()->clear();
        $this->getRepeatingQueue()->clear();
        $this->redis->del('resque:queues');
    }

    /**
     * Get an array of all known or given queues
     *
     * @param  array           $selected
     * @return ArrayCollection
     */
    public function getQueues($selected = array())
    {
        $array  = array();
        $queues = $selected;
        if (empty($selected)) {
            $queues = $this->redis->smembers('resque:queues') ?: array();
        }
        foreach ($queues as $queue) {
            $array[$queue] = $this->getQueue($queue);
        }

        return new ArrayCollection($array);
    }

    /**
     * Get queue class
     *
     * @param  string $name
     * @return Queue
     */
    public function getQueue($name)
    {
        return new Queue($this->redis, $name);
    }

    /**
     * Get delayed queue class
     *
     * @return DelayedQueue
     */
    public function getDelayedQueue()
    {
        return new DelayedQueue($this->redis);
    }

    /**
     * Get repeating queue class
     *
     * @return RepeatingQueue
     */
    public function getRepeatingQueue()
    {
        return new RepeatingQueue($this->redis);
    }

    /**
     * Register worker
     *
     * @param string $id
     */
    public function registerWorker($id)
    {
        $this->redis->sadd('resque:workers', $id);
        $this->redis->set("resque:worker:$id:started", strftime('%a %b %d %H:%M:%S %Z %Y'));
    }

    /**
     * Unregister worker
     *
     * @param string $id
     */
    public function unregisterWorker($id)
    {
        $this->redis->srem('resque:workers', $id);
        $this->redis->del("resque:worker:$id");
        $this->redis->del("resque:worker:$id:started");

        $this->redis->del("stat:processed:$id");
        $this->redis->del("stat:failed:$id");
    }

    /**
     * Push a job to the end of a specific queue. 
     * If the queue does not exist, then create it as well.
     * 
     * @param  string $queue
     * @param  string $class
     * @param  array  $args
     * @return string Enqueued job id
     */
    public function enqueue($queue, $class, $args = array())
    {
        $id = $this->getQueue($queue)->put(array(
            'queue' => $queue,
            'class' => $class,
            'args'  => $args
        ));
        $this->updateStatus($id, Job::STATUS_WAITING);

        if ($this->logger) {
            $this->logger->addNotice("Job $id enqueued in $queue", array(
                'class' => $class,
                'args'  => $args
            ));
        }

        return $id;
    }

    /**
     * Push a job to the end of delayed queue. 
     * If the queue does not exist, then create it as well.
     *
     * @param  DateTime $at
     * @param  string   $queue
     * @param  string   $class
     * @param  array    $args
     * @return string   Enqueued job id
     */
    public function enqueueDelayed(\DateTime $at, $queue, $class, $args = array())
    {
        $id = $this->getDelayedQueue()->put(array(
            'at'    => $at,
            'queue' => $queue,
            'class' => $class,
            'args'  => $args
        ));

        if ($this->logger) {
            $datestr = $at->format('Y-m-d\TH:i:s');
            $this->logger->addNotice("Delayed job $datestr $id enqueued in $queue", array(
                'class' => $class,
                'args'  => $args
            ));
        }

        return $id;
    }

    /**
     * Push a job to the end of repeating queue. 
     * If the queue does not exist, then create it as well.
     *
     * @param  string $queue
     * @param  string $class
     * @param  array  $args
     * @return string Enqueued job id
     */
    public function enqueueRepeating($intervalSpec, $queue, $class, $args = array())
    {
        $id = $this->getRepeatingQueue()->put(array(
            'interval' => $intervalSpec,
            'queue'    => $queue,
            'class'    => $class,
            'args'     => $args
        ));

        if ($this->logger) {
            $this->logger->addNotice("Repeating job $intervalSpec $id enqueued in $queue", array(
                'class' => $class,
                'args'  => $args
            ));
        }

        return $id;
    }

    /**
     * Update status
     * 
     * @param string $id
     * @param int    $status
     */
    public function updateStatus($id, $status)
    {
        $this->redis->set("resque:job:$id:status", json_encode(array(
            'status' => $status,
            'updated' => time(),
        )));
    }

    /**
     * Get redis client
     * 
     * @return Client
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Get event dispatcher
     * 
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Set logger
     * 
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger
     * 
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}