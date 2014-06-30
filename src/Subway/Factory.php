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

use Subway\Events;
use Subway\Queue;
use Subway\Job;
use Subway\Event\EnqueueEvent;
use Subway\Event\StatusEvent;
use Subway\Event\EventSubscriber;
use Subway\Queue\DelayedQueue;
use Subway\Queue\RepeatingQueue;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Class constructor
     * 
     * @param Client          $redis
     * @param EventDispatcher $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(Client $redis, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->redis      = $redis;
        $this->dispatcher = $dispatcher;
        $this->logger     = $logger;

        if ($dispatcher) {
            $subscriber = new EventSubscriber($this);
            $this->dispatcher->addSubscriber($subscriber);
        }
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
     * Get registered workers
     * 
     * @return array
     */
    public function getWorkers()
    {
        return $this->redis->smembers('resque:workers');
    }

    /**
     * Register worker
     *
     * @param string $id Worker id
     */
    public function registerWorker($id)
    {
        $this->redis->sadd('resque:workers', $id);
        $this->redis->set("resque:worker:$id:started", strftime('%a %b %d %H:%M:%S %Z %Y'));
    }

    /**
     * Unregister worker
     *
     * @param string $id Worker id
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
     * @param  Message $message
     * @return string  Enqueued job id
     */
    public function enqueue(Message $message)
    {
        $queue = $this->guessMessageQueue($message);
        $queue->put($message);
        $this->updateStatus($message, Job::STATUS_WAITING);

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(Events::ENQUEUE, new EnqueueEvent($message));
        }

        $this->messageAwareLog(LogLevel::NOTICE, sprintf('Job %s enqueued in %s', $message->getId(), $queue->getName()), $message);

        return $message->getId();
    }

    /**
     * Push a job to the end of a specific queue. 
     * If the queue does not exist, then create it as well.
     * 
     * @param  Message $message
     * @return string  Enqueued job id
     */
    public function enqueueOnce(Message $message)
    {
        $lonerKey = $this->getLonerKey($message);

        if ($this->redis->exists($lonerKey)) {
            $this->messageAwareLog(LogLevel::NOTICE, sprintf('Job with hash %s already exists', $message->getHash()), $message);

            return $this->redis->get($lonerKey);
        }

        $id = $this->enqueue($message);
        $this->redis->set($lonerKey, $id);

        return $id;
    }

    public function getLonerKey(Message $message)
    {
        return sprintf('resque:loners:queue:%s:job:%s', $message->getQueue(), $message->getHash());
    }

    /**
     * Message aware log
     * 
     * @param string  $level
     * @param string  $str
     * @param Message $message
     */
    protected function messageAwareLog($level, $str, Message $message)
    {
        if ($this->logger) {
            $this->logger->log($level, $str, array(
                'class' => $message->getClass(),
                'args'  => $message->getArgs()
            ));
        }
    }

    /**
     * Guess message queue
     *
     * @param  Message $message
     * @return Queue
     */
    protected function guessMessageQueue(Message $message)
    {
        if ($message->getAt() && $message->getInterval()) {
            // Repeating
            $queue = $this->getRepeatingQueue();
        } elseif ($message->getAt()) {
            // Delayed
            $queue = $this->getDelayedQueue();
        } else {
            // Good old fashion job
            $queue = $this->getQueue($message->getQueue());
        }

        return $queue;
    }

    /**
     * Update status
     * 
     * @param Message $message
     * @param integer $status
     */
    public function updateStatus(Message $message, $status)
    {
        $id = $message->getId();

        $this->redis->set("resque:job:$id:status", json_encode(array(
            'status'  => $status,
            'updated' => time(),
        )));

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(Events::STATUS, new StatusEvent($message, $status));
        }

        // Set an expiration for completed jobs
        switch ($status) {
            case Job::STATUS_COMPLETE:
                // 432000 seconds = 5 Days
                $this->redis->expire("resque:job:$id:status", 432000);
                break;
        }
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
     * Get logger
     * 
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}