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
            'concurrent'       => 5
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
        $cwd = getcwd();
        chdir(sys_get_temp_dir());
        $tmp = 'subway.yml.dist';
        file_put_contents($tmp, 'redis_host: 127.0.0.1:6379');
        
        $config = new Config();
        $this->assertEquals($config->get('redis_host'), '127.0.0.1:6379');

        unlink($tmp);
        chdir($cwd);
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