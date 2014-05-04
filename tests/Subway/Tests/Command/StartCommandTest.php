<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Command;

use Subway\Application;
use Subway\Command\StartCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Worker command class test
 * extends \PHPUnit_Framework_TestCase
 */
class StartCommandTest
{
    /**
     * Test execute
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new StartCommand());

        $command = $application->find('start');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());
    }
}