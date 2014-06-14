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

use Subway\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Symfony console application class test
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test get
     */
    public function testGet()
    {
        $application = new Application('Subway', 'test');
        $command     = $application->get('help');
        
        $this->assertTrue($command instanceof Command);
    }

    /**
     * Test run
     */
    public function testRun()
    {        
        $application = new Application('Subway', 'test');
        $application->setAutoExit(false);
        $application->setDefaultCommand('status');

        $tester = new ApplicationTester($application);
        $tester->run(array(''));
    }

    /**
     * Test help text
     */
    public function testHelp()
    {
        $application = new Application('Subway', 'test');
        $application->getHelp();
    }

    /**
     * Test application commands
     */
    public function testCommands()
    {
        $application = new Application('Subway', 'test');
        $commands    = array_keys($application->all());
        
        $this->assertEquals($commands, array('help', 'list', 'worker', 'status', 'clear', 'self-update', 'selfupdate', 'sample', 'init'));
    }
}