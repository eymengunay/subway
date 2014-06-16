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

use Subway\Factory;
use Subway\Message;
use Subway\Tests\TestCase;
use Predis\Client;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Factory class test
 */
class FactoryTest extends TestCase
{
    /**
     * Test get queue
     */
    public function testGetQueue()
    {
        foreach ($this->queues as $name) {
            $queue = $this->factory->getQueue($name);

            $this->assertEquals($queue->getName(), $name);
        }
    }

    /**
     * Test enqueue
     *
     * @depends testGetQueue
     */
    public function testEnqueue()
    {
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $id = $this->factory->enqueue($message);

        $this->assertTrue((bool) $id);
    }

    /**
     * Test get queues
     *
     * @depends testEnqueue
     */
    public function testGetQueues()
    {
        $queues = $this->factory->getQueues();
        $keys   = $queues->getKeys();
        sort($keys);

        $this->assertEquals($keys, $this->queues);

        $this->factory->clear();
        $queues = $this->factory->getQueues();
        $this->assertEquals($queues->toArray(), array());
    }

    /**
     * Test enqueue once
     *
     * @depends testGetQueues
     */
    public function testEnqueueOnce()
    {
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $id1 = $this->factory->enqueueOnce($message);
        $id2 = $this->factory->enqueueOnce($message);

        $this->assertTrue((bool) $id1);
        $this->assertEquals($id1, $id2);
    }

    /**
     * Test get redis
     */
    public function testGetRedis()
    {
        $redis = $this->factory->getRedis();

        $this->assertTrue($redis instanceof Client);
    }

    /**
     * Test get event dispatcher
     */
    public function testGetEventDispatcher()
    {
        $redis      = $this->factory->getRedis();
        $dispatcher = new EventDispatcher();
        $factory    = new Factory($redis, $dispatcher);

        $this->assertTrue($factory->getEventDispatcher() instanceof EventDispatcherInterface);
    }

    /**
     * Test workers
     */
    public function testWorkers()
    {
        $workers = $this->factory->getWorkers();

        $this->assertTrue(is_array($workers));
        $this->assertEquals(0, count($workers));

        $this->factory->registerWorker('test');
        $this->assertEquals(1, count($this->factory->getWorkers()));

        $this->factory->unregisterWorker('test');
        $this->assertEquals(0, count($this->factory->getWorkers()));
    }

    /**
     * Test set logger
     */
    public function testSetLogger()
    {
        $redis   = $this->factory->getRedis();
        $logger  = new Logger('subway');
        $factory = new Factory($redis, null, $logger);

        $this->assertEquals($logger, $factory->getLogger());
    }
}