<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Exception;

use Subway\Exception\SubwayException;

/**
 * Base exception class
 */
class SubwayExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test exception
     * 
     * @expectedException Subway\Exception\SubwayException
     */
    public function testException()
    {
        throw new SubwayException('Something terrible happened!');
    }
}