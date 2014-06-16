<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway;

use Subway\Command as Commands;
use Subway\Exception\SubwayException;
use Predis\Client;
use Monolog\Logger;
use Pimple;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony console application class
 */
class Application extends BaseApplication
{
    /**
     * @var Pimple
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = 'Subway', $version = 'UNKNOWN')
    {
        $this->container = new Pimple();

        parent::__construct($name, $version);
    }

    /**
     * Get container
     * 
     * @return Pimple
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new Commands\WorkerCommand();
        $defaultCommands[] = new Commands\StatusCommand();
        $defaultCommands[] = new Commands\ClearCommand();
        $defaultCommands[] = new Commands\SelfUpdateCommand();
        $defaultCommands[] = new Commands\SampleCommand();
        $defaultCommands[] = new Commands\InitCommand();

        return $defaultCommands;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        // Prepare container
        if ($command instanceof Commands\ContainerAwareCommand) {
            // Prepare command input
            $command->getSynopsis();
            $command->mergeApplicationDefinition();
            $input->bind($command->getDefinition());

            // Register config service
            $config = new Config();
            $this->container['config'] = function() use ($config) {
                return $config;
            };
            chdir($this->container['config']->get('root'));

            // Register redis service
            try {
                $redis = new Client(sprintf('tcp://%s', $config->get('redis_host')), array('prefix' => $config->get('redis_prefix')));
                $redis->connect();
            } catch (\Exception $e) {
                return $output->writeln('<error> An error occured while connecting to redis! </error>');
            }
            $this->container['redis'] = function() use ($redis) {
                return $redis;
            };

            // Register bridge service
            $defaultBridgeClass = sprintf('Subway\Bridge\%sBridge', ucfirst($config->get('bridge')));
            if (class_exists($defaultBridgeClass)) {
                $bridgeClass = $defaultBridgeClass;
            } else {
                throw new SubwayException('Bridge '.$config->get('bridge').' not found');
            }
            $bridge = new $bridgeClass($config->get('bridge_options'));
            $this->container['bridge'] = function() use ($bridge) {
                return $bridge;
            };

            // Register logger service
            $level = $this->guessLoggerLevel($output);
            $this->container['logger'] = function() use ($bridge, $level) {
                return $bridge->getLogger($level);
            };

            // Register event dispatcher service
            $this->container['event_dispatcher'] = function() use ($bridge) {
                return $bridge->getEventDispatcher();
            };

            // Register factory service
            $factory = new Factory($redis, $this->container['event_dispatcher'], $this->container['logger']);
            $this->container['factory'] = function() use ($factory) {
                return $factory;
            };
        }

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * Guess logger level
     *
     * Default level: WARNING
     *
     * -q:   ERROR
     * -v:   NOTICE
     * -vv:  INFO
     * -vvv: DEBUG
     * 
     * @param  OutputInterface $output
     * @return string
     */
    protected function guessLoggerLevel(OutputInterface $output)
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_QUIET:
                $level = Logger::ERROR;
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                $level = Logger::NOTICE;
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $level = Logger::INFO;
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $level = Logger::DEBUG;
                break;
            case OutputInterface::VERBOSITY_NORMAL:
            default:
                $level = Logger::WARNING;
                break;
        }

        return $level;
    }

    /**
     * Get logo
     * 
     * @return string
     */
    public function getLogo()
    {
        return "           _                       
 ___ _   _| |____      ____ _ _   _ 
/ __| | | | '_ \ \ /\ / / _` | | | |
\__ \ |_| | |_) \ V  V / (_| | |_| |
|___/\__,_|_.__/ \_/\_/ \__,_|\__, |
                              |___/ 
\n";
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return $this->getLogo() . parent::getHelp();
    }
}