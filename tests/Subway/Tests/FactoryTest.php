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
use Predis\Client;
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
        $redis = $this->factory->getEventDispatcher();

        $this->assertTrue($redis instanceof EventDispatcherInterface);
    }
}