<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Queue;

use Subway\Event\EventSubscriber;
use Subway\Tests\TestCase;

/**
 * Event subscriber test
 */
class EventSubscriberTest extends TestCase
{
    /**
     * Test construct
     */
    public function testConstruct()
    {
        $subscriber = new EventSubscriber($this->factory);
    }
}