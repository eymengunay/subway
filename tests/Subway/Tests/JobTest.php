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

/**
 * Job class test
 */
class JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test job
     */
    public function testJob()
    {
        $args = array();
        $stub = $this->getMockForAbstractClass('Subway\Job');
        $stub->expects($this->any())
             ->method('perform')
             ->will($this->returnValue(TRUE))
        ;

        $this->assertTrue($stub->perform());
    }

    /**
     * Test name
     */
    public function testName()
    {
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $job     = $message->getJobInstance();

        $this->assertEquals($job->getName(), 'Md5Job');
    }

    /**
     * Test name
     */
    public function testPerform()
    {
        $message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $job     = $message->getJobInstance();

        $job->perform();
    }
}