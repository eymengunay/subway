<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Event;

use Subway\Job;
use Subway\Message;
use Subway\Event\StatusEvent;

/**
 * Status event class test
 */
class StatusEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test get message
     */
    public function testGetMessage()
    {
        $status  = Job::STATUS_WAITING;
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $event   = new StatusEvent($message, $status);

        $this->assertEquals($message, $event->getMessage());
        $this->assertEquals($status, $event->getStatus());
    }
}