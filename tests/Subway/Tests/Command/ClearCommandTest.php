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
use Subway\Command\ClearCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Clear command test
 */
class ClearCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test execute
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new ClearCommand());

        $command       = $application->find('clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('--no-interaction' => true));

        $display = $commandTester->getDisplay();

        $this->assertEquals('Database cleared successfully', trim($display));
    }
}