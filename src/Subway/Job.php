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

/**
 * Job class
 */
abstract class Job
{
    const STATUS_WAITING  = 1;
    const STATUS_RUNNING  = 2;
    const STATUS_FAILED   = 3;
    const STATUS_COMPLETE = 4;

    /**
     * @var Message
     */
    protected $message;

    /**
     * Set message
     * 
     * @param  Message $message
     * @return self
     */
    final public function setMessage(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Perform job
     * 
     * @param array $args 
     */
    abstract public function perform();
}