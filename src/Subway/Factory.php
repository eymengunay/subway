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
use Subway\Event\EventSubscriber;
use Subway\Queue\DelayedQueue;
use Subway\Queue\RepeatingQueue;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Monolog\Handler\RedisHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
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
        $this->initialize($redis);
    }

    /**
     * Initialize factory
     *
     * @param Client $redis
     */
    protected function initialize(Client $redis)
    {
        $this->redis = $redis;
        $this->dispatcher = new EventDispatcher();
        $this->logger = new Logger('subway');
        $this->logger->pushProcessor(new MemoryPeakUsageProcessor());
        $this->logger->pushHandler(new RedisHandler($redis, 'resque:logs', Logger::WARNING));

        $subscriber = new EventSubscriber($this);
        $this->dispatcher->addSubscriber($subscriber);
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
        $this->updateStatus($message->getId(), Job::STATUS_WAITING);

        $this->getEventDispatcher()->dispatch(Events::ENQUEUE, new EnqueueEvent($message));

        if ($this->logger) {
            $this->logger->addNotice(sprintf('Job %s enqueued in %s', $message->getId(), $queue->getName()), array(
                'class' => $message->getClass(),
                'args'  => $message->getArgs()
            ));
        }

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
        $lonerKey = sprintf('resque:loners:queue:%s:job:%s', $message->getQueue(), $message->getHash());

        if ($this->redis->has($lonerKey)) {
            $this->logger->addNotice(sprintf('Job with hash %s already exists', $message->getHash()), array(
                'class' => $message->getClass(),
                'args'  => $message->getArgs()
            ));

            return $this->redis->get($lonerKey);
        }

        $id = $this->enqueue($message);
        $this->redis->set($lonerKey, $id);

        return $id;
    }

    /**
     * Guess message queue
     *
     * @param  Message $message
     * @return string
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
     * @param string $id
     * @param int    $status
     */
    public function updateStatus($id, $status)
    {
        $this->redis->set("resque:job:$id:status", json_encode(array(
            'status'  => $status,
            'updated' => time(),
        )));

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
     * Set logger
     * 
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
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