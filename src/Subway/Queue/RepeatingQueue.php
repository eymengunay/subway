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
        $data = parent::pop();
        if (is_null($data)) {
            return;
        }

        $this->put($data);

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