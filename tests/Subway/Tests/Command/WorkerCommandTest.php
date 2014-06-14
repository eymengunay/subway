<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Command;

use Subway\Application;
use Subway\Command\WorkerCommand;

/**
 * Worker command test
 */
class WorkerCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test execute
     */
    public function testExecute()
    {
        $command = new WorkerCommand();
    }
}