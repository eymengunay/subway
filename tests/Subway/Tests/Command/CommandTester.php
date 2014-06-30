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

use Subway\Factory;
use Subway\Config;
use Subway\Command\ContainerAwareCommand;
use Predis\Client;
use Monolog\Logger;
use Pimple;
use Symfony\Component\Console\Tester\CommandTester as BaseCommandTester;
use Symfony\Component\Console\Command\Command;

/**
 * Command tester
 */
class CommandTester extends BaseCommandTester
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Command $command)
    {
        $this->bootstrap($command);
        
        parent::__construct($command);
    }

    /**
     * Bootstrap command for testing
     * 
     * @param Command $command
     */
    protected function bootstrap(Command $command)
    {
        if ($command instanceof ContainerAwareCommand) {
            $container = $command->getContainer();
            $redis     = new Client('tcp://127.0.0.1:6379', array('prefix' => 'test:'));
            $factory   = new Factory($redis);
            $logger    = new Logger('subway');
            $config    = new Config();
            $config->set('prefix', 'test:');

            $container['redis'] = function() use ($redis) {
                return $redis;
            };
            $container['logger'] = function() use ($logger) {
                return $logger;
            };
            $container['factory'] = function() use ($factory) {
                return $factory;
            };
        }
    }
}
