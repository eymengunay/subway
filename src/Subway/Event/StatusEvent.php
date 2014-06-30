<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Event;

use Subway\Message;

/**
 * Status event
 */
class StatusEvent extends MessageEvent
{
    /**
     * @var integer
     */
    protected $status;

    /**
     * Class constructor
     *
     * @param Message $message
     * @param integer $status
     */
    public function __construct(Message $message, $status)
    {
        parent::__construct($message);

        $this->status = $status;
    }

    /**
     * Get status
     * 
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}