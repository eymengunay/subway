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

use Subway\Message;
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
        $message = parent::pop();
        if (is_null($message)) {
            return;
        }

        $at = $this->getNextDate($message->getInterval());
        $message->setAt($at);
        $this->put($message);

        return $message;
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
}