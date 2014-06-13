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
use Subway\Command\StatusCommand;

/**
 * Status command test
 */
class StatusCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test execute
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new StatusCommand());

        $command       = $application->find('status');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());

        $json   = $commandTester->getDisplay();
        $status = json_decode($json, true);

        $this->assertTrue(array_key_exists('queue', $status));
        $this->assertTrue(array_key_exists('worker', $status));

        $this->assertTrue(is_array($status['queue']));
        $this->assertTrue(is_array($status['worker']));
    }
}