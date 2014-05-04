<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Queue;

use Predis\Client;
use Subway\Queue;

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
     * Create queue
     */
    public function create()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->redis->zcard(sprintf('resque:queue:%s', $this->getName()));
    }

    /**
     * {@inheritdoc}
     */
    public function getJobs()
    {
        $jobs = $this->redis->zrange(sprintf('resque:queue:%s', $this->getName()), 0, -1) ?: array();
        $data = array();
        foreach ($jobs as $job) {
            $data[] = json_decode($job, true);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        $key = sprintf('resque:queue:%s', $this->getName());
        $item = null;
        $options = array(
            'cas'   => true,    // Initialize with support for CAS operations
            'watch' => $key,    // Key that needs to be WATCHed to detect changes
            'retry' => 3,       // Number of retries on aborted transactions, after
                                // which the client bails out with an exception.
        );

        $this->redis->multiExec($options, function($tx) use ($key, &$item) {
            $max = new \DateTime();
            @list($item) = $tx->zrangebyscore($key, 0, $max->format('U'));

            if (isset($item)) {
                $tx->multi();   // With CAS, MULTI *must* be explicitly invoked.
                $tx->zrem($key, $item);
            }
        });

        if (is_null($item)) {
            return;
        }

        return json_decode($item, true);
    }

    /**
     * {@inheritdoc}
     */
    public function put(array $data)
    {
        $at = $data['at']->format('U');
        unset($data['at']);
        $data['id'] = $this->generateJobId($data);
        $this->redis->zadd(sprintf('resque:queue:%s', $this->getName()), $at, json_encode($data));

        return $data['id'];
    }
}