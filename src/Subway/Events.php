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
     * The on.failure event is thrown on
     * job failure.
     *
     * The event listener receives an
     * Subway\Event\MessageEvent instance.
     *
     * @var string
     */
    const ON_FAILURE = 'on.failure';
}