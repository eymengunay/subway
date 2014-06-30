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
 * Message class test
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test invalid message class
     */
    public function testInvalidClass()
    {
        $this->setExpectedException('Subway\Exception\SubwayException');
        $message = new Message('default', 'Invalid\Namespace\Class', array('hello' => 'world'));
        $message->getJobInstance();
    }
}