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
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony console application class
 */
class Application extends BaseApplication
{
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
    public function get($name)
    {
        $commands = $this->all();
        if (!isset($commands[$name])) {
            throw new \InvalidArgumentException(sprintf('The command "%s" does not exist.', $name));
        }

        $command = $commands[$name];

        if ($command instanceof Commands\ConfigAwareCommand) {
            $definition = $this->getDefinition();
            $definition->addOption(new InputOption('--config', '-c', InputOption::VALUE_REQUIRED, 'Configuration file path.', 'subway.yml'));
        }

        return parent::get($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if ($command instanceof Commands\ConfigAwareCommand) {
            // Get & check configuration file
            $file = $input->getParameterOption(array('--config', '-c')) ?: null;
            if (file_exists($file) === false) {
                $output->writeln("<comment>WARNING: Configuration file not found, using default values!</comment>");
            }

            // Create config class
            $config = new Config($file);
            $command->setConfig($config);

            // Autoloader
            $autoload = $config->get('autoload');
            if (file_exists($autoload) === false) {
                throw new SubwayException('Autoload file not found');
            }
            require_once $autoload;
            
            // Connect to redis
            try {
                $redis = new Client(sprintf('tcp://%s', $config->get('host')), array('prefix' => $config->get('prefix')));
                $redis->connect();
                $command->setRedis($redis);
            } catch (\Exception $e) {
                return $output->writeln('<error> An error occured while connecting to redis. </error>');
            }

            // Logger
            $logger = new Logger('subway');
            $logger->pushProcessor(new MemoryPeakUsageProcessor());
            $logger->pushHandler(new RotatingFileHandler($config->get('log'), 0, $this->guessLoggerLevel($output)));
            $command->setLogger($logger);

            // Factory
            $factory = new Factory($redis);
            $factory->setLogger($logger);
            $command->setFactory($factory);
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