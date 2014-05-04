<?php

namespace Subway\Test;

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