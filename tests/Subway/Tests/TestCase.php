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
use Predis\Client;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $queues;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->redis   = new Client('tcp://127.0.0.1:6379', array('prefix' => 'test:'));
        $this->factory = new Factory($this->redis);
        $this->queues  = array(
            'default',
            'message',
            'newsletter',
            'thumbnail'
        );
    }
}