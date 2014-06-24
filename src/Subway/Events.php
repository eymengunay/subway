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
 * Subway events
 */
final class Events
{
    /**
     * The enqueue event is thrown on
     * each time a new job is enqueued.
     *
     * The event listener receives an
     * Subway\Event\EnqueueEvent instance.
     *
     * @var string
     */
    const ENQUEUE = 'enqueue';

    /**
     * The status event is thrown on
     * each time a job status gets updated.
     *
     * The event listener receives an
     * Subway\Event\StatusEvent instance.
     *
     * @var string
     */
    const STATUS = 'status';
}