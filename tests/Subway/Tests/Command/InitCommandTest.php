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
 * Init command test
 */
class InitCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test execute
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new InitCommand());

        $command       = $application->find('init');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('--force' => true));

        $display = $commandTester->getDisplay();
        $this->assertEquals('Subway configuration file subway.yml created successfully!', trim($display));
    }
}