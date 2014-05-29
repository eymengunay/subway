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

use Subway\Message;
use Subway\Event\EnqueueEvent;

/**
 * Enqueue event test
 */
class EnqueueEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test get message
     */
    public function testGetMessage()
    {
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $event   = new EnqueueEvent($message);

        $this->assertEquals($message, $event->getMessage());
    }
}