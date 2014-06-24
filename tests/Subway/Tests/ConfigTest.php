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

use Subway\Config;

/**
 * Config test
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test all
     */
    public function testAll()
    {
        $config = new Config();
        $this->assertEquals(array(
            'redis_host'       => 'localhost:6379',
            'redis_prefix'     => null,
            'bridge'           => 'composer',
            'bridge_options'   => array(),
            'interval'         => 5,
            'concurrent'       => 5,
            'root'             => getcwd()
        ), $config->all());
    }

    /**
     * Test get
     */
    public function testGet()
    {
        $config = new Config();
        $this->assertEquals($config->get('redis_host'), 'localhost:6379');
    }

    /**
     * Test existence
     */
    public function testExistence()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'subway.yml');
        file_put_contents($tmp, 'redis_host: 127.0.0.1:6379');
        
        $config = new Config($tmp);
        $this->assertEquals($config->get('redis_host'), '127.0.0.1:6379');

        unlink($tmp);
    }

    /**
     * Test inexistence
     */
    public function testInexistence()
    {
        $config = new Config('/go-ahead-make-my-day');
        $this->assertEquals($config->get('redis_host'), 'localhost:6379');
    }
}