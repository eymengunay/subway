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

/**
 * Repeating queue class
 */
class RepeatingQueue extends DelayedQueue
{
    /**
     * Class constructor
     *
     * @param Client $redis
     */
    public function __construct(Client $redis)
    {
        parent::__construct($redis, 'repeating');
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        $key = sprintf('resque:queue:%s', $this->getName());
        $data = null;
        $options = array(
            'cas'   => true,    // Initialize with support for CAS operations
            'watch' => $key,    // Key that needs to be WATCHed to detect changes
            'retry' => 3,       // Number of retries on aborted transactions, after
                                // which the client bails out with an exception.
        );

        $this->redis->multiExec($options, function($tx) use ($key, &$data) {
            $max = new \DateTime();
            @list($item) = $tx->zrangebyscore($key, 0, $max->format('U'));

            if (isset($item)) {
                $data = json_decode($item, true);
                $at = $this->getNextDate($data['interval'])->format('U');
                $tx->multi();   // With CAS, MULTI *must* be explicitly invoked.
                $tx->zadd($key, $at, $item);
            }
        });

        return $data;
    }

    /**
     * Get next date
     * 
     * @param  string   $intervalSpec
     * @return DateTime
     */
    private function getNextDate($intervalSpec)
    {
        $period = new \DatePeriod(new \DateTime(), new \DateInterval($intervalSpec), 1, \DatePeriod::EXCLUDE_START_DATE);
        $array = iterator_to_array($period);

        return current($array);
    }

    /**
     * {@inheritdoc}
     */
    public function put(array $data)
    {
        $data['at'] = $this->getNextDate($data['interval']);

        return parent::put($data);
    }
}