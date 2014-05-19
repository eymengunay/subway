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
        $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array('hello' => 'world')
        ));
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
        $id    = $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array(
                'hello' => 'world'
            )
        ));
        $id    = $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array(
                'hello' => 'world'
            )
        ));
        $id    = $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array(
                'hello' => 'world'
            )
        ));
        $id    = $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array(
                'hello' => 'world'
            )
        ));
        $id    = $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array(
                'hello' => 'world'
            )
        ));
        $id    = $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array(
                'hello' => 'world'
            )
        ));
        $id    = $queue->put(array(
            'class' => 'Acme\Job\Class', 
            'args'  => array(
                'hello' => 'world'
            )
        ));

        $this->assertTrue((bool) $id);
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
        $job   = $queue->pop();

        // Unset unique job id for assertion
        unset($job['id']);

        $this->assertEquals($job, array(
            'class' => 'Acme\Job\Class',
            'args'  => array(
                'hello' => 'world'
            )
        ));

        $queue->pop();
    }
}