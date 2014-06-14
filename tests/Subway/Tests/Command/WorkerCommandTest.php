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
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Worker command test
 */
class WorkerCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test welcome
     */
    public function testWelcome()
    {
        $application = new Application();
        $application->add(new WorkerCommand());
        
        $command = $application->find('worker');
        $method  = new \ReflectionMethod(
          'Subway\Command\WorkerCommand', 'getWelcome'
        );
        $method->setAccessible(true);
        $welcome = $method->invoke($command);

        $this->assertEquals(gettype($welcome), 'string');
    }

    /**
     * Test delayed timer
     */
    public function testDelayedTimer()
    {
        $application = new Application();
        $application->add(new WorkerCommand());
        
        $command = $application->find('worker');
        $method  = new \ReflectionMethod(
          'Subway\Command\WorkerCommand', 'delayedTimer'
        );
        $method->setAccessible(true);
        $method->invokeArgs($command, array(new ConsoleOutput()));
    }

    /**
     * Test repeating timer
     */
    public function testrepeatingTimer()
    {
        $application = new Application();
        $application->add(new WorkerCommand());
        
        $command = $application->find('worker');
        $method  = new \ReflectionMethod(
          'Subway\Command\WorkerCommand', 'repeatingTimer'
        );
        $method->setAccessible(true);
        $method->invokeArgs($command, array(new ConsoleOutput()));
    }
}