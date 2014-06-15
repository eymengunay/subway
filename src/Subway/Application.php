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
use Predis\Client;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Pimple;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->container['config'] = function() {
            return new Config();
        };

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
    public function add(Command $command)
    {
        $command = parent::add($command);

        if ($command instanceof Commands\ConfigAwareCommand) {
            $command->configureInputDefinition();
        }

        return $command;
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
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        
        $options = array(
            new InputOption('--cwd', '-c', InputOption::VALUE_REQUIRED, 'Working directory path.'),
        );
        $definition->setOptions(array_merge($options, $definition->getOptions()));

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        // Configure
        if ($command instanceof Commands\ConfigAwareCommand) {
            $command->processConfiguration($input, $output);
        }

        // Prepare container
        if ($command instanceof Commands\ContainerAwareCommand) {
            $config = $this->container['config'];
            // Register redis service
            try {
                $redis = new Client(sprintf('tcp://%s', $config->get('host')), array('prefix' => $config->get('prefix')));
                $redis->connect();
            } catch (\Exception $e) {
                return $output->writeln('<error> An error occured while connecting to redis! </error>');
            }
            $this->container['redis'] = function() use ($redis) {
                return $redis;
            };

            // Register logger service
            $logger = new Logger('subway');
            $logger->pushProcessor(new MemoryPeakUsageProcessor());
            $logger->pushHandler(new RotatingFileHandler($config->get('log'), 0, $this->guessLoggerLevel($output)));
            $this->container['logger'] = function() use ($logger) {
                return $logger;
            };

            // Register factory service
            $factory = new Factory($redis);
            $factory->setLogger($logger);
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