<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests;

use Subway\Message;
use Subway\Test\TestCase;

/**
 * Queue class test
 */
class QueueTest extends TestCase
{
    /**
     * Test queue clear
     */
    public function testQueueClear()
    {
        $queue  = $this->factory->getQueue('default');
        $count1 = $queue->count();
        $message = new Message($queue->getName(), 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $queue->put($message);
        $count2 = $queue->count();

        $this->assertNotEquals($count1, $count2);

        $queue->clear();
        $count3 = $queue->count();
        $this->assertEquals($count3, 0);
    }

    /**
     * Test queue count
     *
     * @depends testQueueClear
     */
    public function testQueueCount()
    {
        $queue = $this->factory->getQueue('default');
        $count = $queue->count();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test queue name
     */
    public function testQueueName()
    {
        $queue = $this->factory->getQueue('default');
        
        $this->assertEquals($queue->getName(), 'default');
    }

    /**
     * Test queue put
     *
     * @depends testQueueClear
     */
    public function testQueuePut()
    {
        $queue = $this->factory->getQueue('default');
        $message = new Message($queue->getName(), 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));

        $queue->put($message);

        $this->assertTrue((bool) $message->getId());
    }

    /**
     * Test queue get jobs
     *
     * @depends testQueuePut
     */
    public function testQueueGetJobs()
    {
        $queue = $this->factory->getQueue('default');
        $jobs  = $queue->getJobs();

        $this->assertTrue(is_array($jobs));
        $this->assertGreaterThanOrEqual(0, count($jobs));
    }

    /**
     * Test queue pop
     *
     * @depends testQueuePut
     */
    public function testQueuePop()
    {
        $queue = $this->factory->getQueue('default');
        $message = $queue->pop();

        $this->assertEquals('Subway\Tests\Job\Md5Job', $message->getClass());
        $this->assertEquals(array('hello' => 'world'), $message->getArgs()->toArray());
    }
}