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
use Subway\Command\InitCommand;

/**
 * Self update command test
 */
class SelfUpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test execute
     */
    public function testExecute()
    {
        $application = new Application('Subway', '999999.9.9');
        $application->add(new InitCommand());

        $command       = $application->find('self-update');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());

        $display = $commandTester->getDisplay();
        $this->assertTrue((bool) strpos($display, 'Already up-to-date'));
    }
}