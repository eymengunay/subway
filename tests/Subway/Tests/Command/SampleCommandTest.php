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
use Subway\Command\SampleCommand;
use Pimple;

/**
 * Sample command test
 */
class SampleCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test execute
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new SampleCommand());

        $command       = $application->find('sample');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());

        $display = $commandTester->getDisplay();

        $this->assertRegExp('/Job .* enqueued in sample/i', $display);

        $this->assertTrue($command->getContainer() instanceof Pimple);
    }
}