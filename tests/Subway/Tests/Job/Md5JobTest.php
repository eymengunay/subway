<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Job;

use Subway\Job;
use Subway\Message;

/**
 * Md5 job test
 */
class Md5JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function testPerform()
    {
        $message = new Message('defult', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));

        $job = new Md5Job();
        $job->setMessage($message);
        $job->perform();
    }
}
