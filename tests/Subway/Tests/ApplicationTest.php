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

/**
 * Symfony console application class test
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
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
        
        $this->assertEquals($commands, array('help', 'list', 'worker', 'status', 'clear', 'self-update', 'selfupdate'));
    }
}