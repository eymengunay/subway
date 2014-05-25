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
use Subway\Test\TestCase;

/**
 * Repeating queue class test
 */
class RepeatingQueueTest extends TestCase
{
    /**
     * Test queue count
     */
    public function testQueueCount()
    {
        $queue = $this->factory->getRepeatingQueue();
        $count = $queue->count();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test queue name
     */
    public function testQueueName()
    {
        $queue = $this->factory->getRepeatingQueue();
        
        $this->assertEquals($queue->getName(), 'repeating');
    }

    /**
     * Test queue put
     */
    public function testQueuePut()
    {
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $message->setAt(new \DateTime('-29 second'));
        $message->setInterval('PT10S');

        $this->factory->enqueue($message);

        $this->assertTrue((bool) $message->getId());
    }

    /**
     * Test queue pop
     *
     * @depends testQueuePut
     */
    public function testQueuePop()
    {
        $queue = $this->factory->getRepeatingQueue();
        $message = $queue->pop();

        $this->assertEquals('Subway\Tests\Job\Md5Job', $message->getClass());
        $this->assertEquals(array('hello' => 'world'), $message->getArgs()->toArray());
    }
}