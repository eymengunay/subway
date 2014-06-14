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

use Subway\Config;
use Subway\Factory;
use Subway\Application;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Subway\Command\SampleCommand;

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

        $this->assertTrue($command->getRedis() instanceof Client);
        $this->assertTrue($command->getFactory() instanceof Factory);
        $this->assertTrue($command->getConfig() instanceof Config);
        $this->assertTrue($command->getLogger() instanceof LoggerInterface);
    }
}