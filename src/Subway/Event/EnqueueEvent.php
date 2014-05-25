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
use Symfony\Component\EventDispatcher\Event;

/**
 * Enqueue event
 */
class EnqueueEvent extends Event
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * Class constructor
     * 
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get message
     * 
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}