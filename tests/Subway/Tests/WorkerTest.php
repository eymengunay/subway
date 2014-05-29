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
use Subway\Worker;
use Subway\Tests\TestCase;

/**
 * Worker class test
 */
class WorkerTest extends TestCase
{
    /**
     * Test worker perform
     */
    public function testWorker()
    {
        $message = new Message('defult', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
        $job     = $message->getJobInstance();
        $worker  = new Worker('id', $this->factory);
        $result  = $worker->perform($job);

        $this->assertTrue($result);
    }
}