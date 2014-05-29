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
 * Delayed queue class test
 */
class DelayedQueueTest extends TestCase
{
    /**
     * Test queue count
     */
    public function testQueueCount()
    {
        $queue = $this->factory->getDelayedQueue();
        $count = $queue->count();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test queue name
     */
    public function testQueueName()
    {
        $queue = $this->factory->getDelayedQueue();
        
        $this->assertEquals($queue->getName(), 'delayed');
    }

    /**
     * Test queue put
     */
    public function testQueuePut()
    {
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $message->setAt(new \DateTime('-29 second'));

        $this->factory->enqueue($message);

        $this->assertTrue((bool) $message->getId());
    }

    /**
     * Test queue get jobs
     *
     * @depends testQueueCount
     */
    public function testQueueGetJob()
    {
        $queue = $this->factory->getDelayedQueue();
        $messages = $queue->getJobs();
        $message = current($messages);

        $this->assertEquals('array', gettype($messages));
        $this->assertEquals('default', $message->getQueue());
    }

    /**
     * Test queue get jobs
     *
     * @depends testQueueGetJob
     */
    public function testQueueGetNextMessage()
    {
        $queue   = $this->factory->getDelayedQueue();
        $message = $queue->getNextMessage();

        $this->assertTrue($message instanceof Message);
        $this->assertTrue(is_array($message->getArgs()->toArray()));
    }

    /**
     * Test queue pop
     *
     * @depends testQueueGetJob
     */
    public function testQueuePop()
    {
        $queue = $this->factory->getDelayedQueue();
        $message = $queue->pop();

        $this->assertEquals('Subway\Tests\Job\Md5Job', $message->getClass());
        $this->assertEquals(array('hello' => 'world'), $message->getArgs()->toArray());
    }
}