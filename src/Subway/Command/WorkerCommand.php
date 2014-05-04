<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Command;

use Subway\Factory;
use Subway\Worker;
use Subway\Exception\SubwayException;
use React\EventLoop\Factory as React;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Worker command
 */
class WorkerCommand extends RedisAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('worker')
            ->setDescription('Starts a subway worker')

            ->addOption('cwd', 'w', InputOption::VALUE_OPTIONAL, 'Current working directory')
            ->addOption('autoload', 'a', InputOption::VALUE_REQUIRED, 'Application autoloader', './vendor/autoload.php')
            ->addOption('log', 'l', InputOption::VALUE_REQUIRED, 'Log file', './subway.log')
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'How often to check for new jobs across the queues', 5)
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Max concurrent fork count', 2)
            ->addOption('detect-leaks', null, InputOption::VALUE_NONE, 'Output information about memory usage')

            ->addArgument('queues', InputArgument::IS_ARRAY, 'Queue names (separate using space)', array())
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if ($cwd = $input->getOption('cwd')) {
            chdir(realpath($cwd));
        }

        $autoload = $input->getOption('autoload');
        if (file_exists($autoload) === false) {
            throw new SubwayException('Autoload file not found');
        }

        require_once $autoload;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getWelcome());

        $factory  = new Factory($this->redis);
        $children = new ArrayCollection();

        $logger = new Logger('subway');
        $logger->pushHandler(new StreamHandler($input->getOption('log'), $this->guessLoggerLevel($output)));
        $factory->setLogger($logger);

        $id = gethostname() . ':'.getmypid() . ':' . implode(',', $input->getArgument('queues') ?: array('*'));
        $factory->registerWorker($id);
        $logger->addInfo("Worker $id is ready");

        declare(ticks=1);
        $loop = React::create();

        // Execute timer
        $timer = $loop->addPeriodicTimer($input->getOption('interval'), function ($timer) use ($id, $input, $output, $factory, $children) {
            // Clear workers
            foreach ($children as $pid => $worker) {
                switch (pcntl_waitpid($pid, $status, WNOHANG)) {
                    case 0:
                    case -1:
                        break;
                    default:
                        $children->remove($pid);
                        break;
                }
            }

            try {
                $queues = $factory->getQueues($input->getArgument('queues'));
            } catch (\Exception $e) {
                $factory->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                throw $e;
            }

            foreach ($queues as $queue) {
                // Check max concurrent limit
                if ($children->count() >= $input->getOption('count')) {
                    $factory->getLogger()->addInfo('Max concurrent limit of '.$input->getOption('count').' reached');
                    break;
                }

                // Pop queue
                try {
                    $job = $queue->pop();
                } catch (\Exception $e) {
                    $factory->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                    throw $e;
                }

                if (!$job) {
                    continue;
                }

                $pid = pcntl_fork();
                if ($pid == -1) {
                    // Wtf?
                    $factory->getLogger()->addError('Could not fork');
                    throw new SubwayException('Could not fork');
                } else if ($pid) {
                    // Parent process
                    $children->set($pid, time());
                    $output->writeln(sprintf('[%s][%s] Starting job. Pid: %s', date('Y-m-d\TH:i:s'), substr($job['id'], 0, 7), $pid));
                } else {
                    // Reconnect to redis
                    $redis = $factory->getRedis();
                    if ($redis->isConnected()) {
                        $redis->disconnect();
                    }
                    $redis->connect();

                    // Child process
                    $worker = new Worker($id, $factory);
                    if ($worker->perform($job)) {
                        $output->writeln(sprintf('<info>[%s][%s] Finised successfully. Mem: %sMB</info>', date('Y-m-d\TH:i:s'), substr($job['id'], 0, 7), round(memory_get_peak_usage() / 1024 / 1024, 2)));
                    } else {
                        $output->writeln(sprintf('<error>[%s][%s] Job execution failed.</error>', date('Y-m-d\TH:i:s'), substr($job['id'], 0, 7)));
                    }

                    posix_kill(getmypid(), 9);
                }
            }
        });

        // Delayed timer
        $timer = $loop->addPeriodicTimer($input->getOption('interval'), function ($timer) use ($input, $output, $factory) {
            $delayedQueue = $factory->getDelayedQueue();
            if ($delayedQueue->count() < 1) {
                return;
            }
            // Pop queue
            try {
                $delayedJob = $delayedQueue->pop();
            } catch (\Exception $e) {
                $factory->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                throw $e;
            }
            if ($delayedJob) {
                $id = $factory->enqueue($delayedJob['queue'], $delayedJob['class'], $delayedJob['args']);

                $factory->getLogger()->addNotice(sprintf('[%s][%s] Delayed job enqueued in %s.', date('Y-m-d\TH:i:s'), $job['id'], $delayedJob['queue']));
                $output->writeln(sprintf('<comment>[%s][%s] Delayed job enqueued in %s.</comment>', date('Y-m-d\TH:i:s'), substr($id, 0, 7), $delayedJob['queue']));
            }
        });

        // Repeating timer
        $timer = $loop->addPeriodicTimer($input->getOption('interval'), function ($timer) use ($input, $output, $factory) {
            $repeatingQueue = $factory->getRepeatingQueue();
            // Pop queue
            try {
                $repeatingJob = $repeatingQueue->pop();
            } catch (\Exception $e) {
                $factory->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                throw $e;
            }
            if ($repeatingJob) {
                $id = $factory->enqueue($repeatingJob['queue'], $repeatingJob['class'], $repeatingJob['args']);

                $factory->getLogger()->addNotice(sprintf('[%s][%s] Repeating job enqueued in %s.', date('Y-m-d\TH:i:s'), $job['id'], $repeatingJob['queue']));
                $output->writeln(sprintf('<comment>[%s][%s] Repeating job enqueued in %s.</comment>', date('Y-m-d\TH:i:s'), substr($id, 0, 7), $repeatingJob['queue']));
            }
        });

        // Detect leaks
        if ( (bool)$input->getOption('detect-leaks') ) {
            $lastPeakUsage = 0;
            $lastUsage = 0;
            $memInfo = function($peak) use (&$lastPeakUsage, &$lastUsage) {
                $lastUsage = ($peak) ? $lastPeakUsage : $lastUsage;
                $info['amount'] = ($peak) ? memory_get_peak_usage() : memory_get_usage();
                $info['diff'] = $info['amount'] - $lastUsage;
                $info['diffPercentage'] = ($lastUsage == 0) ? 0 : $info['diff'] / ($lastUsage / 100);
                $info['statusDescription'] = 'stable';
                $info['statusType'] = 'info';

                if ($info['diff'] > 0)
                {
                    $info['statusDescription'] = 'increasing';
                    $info['statusType'] = 'error';
                }
                else if ($info['diff'] < 0)
                {
                    $info['statusDescription'] = 'decreasing';
                    $info['statusType'] = 'comment';
                }

                // Update last usage variables
                if ($peak) {
                    $lastPeakUsage = $info['amount'];
                } else {
                    $lastUsage = $info['amount'];
                }

                return $info;
            };
            $timer = $loop->addPeriodicTimer(1, function ($timer) use ($input, $output, $memInfo) {
                // Gather memory info
                $peak = $memInfo(true);
                $curr = $memInfo(false);

                // Print report
                $output->writeln('');
                $output->writeln('== MEMORY USAGE ==');
                $output->writeln(sprintf('Peak: %.02f KByte <%s>%s (%.03f %%)</%s>', $peak['amount'] / 1024, $peak['statusType'], $peak['statusDescription'], $peak['diffPercentage'], $peak['statusType']));
                $output->writeln(sprintf('Cur.: %.02f KByte <%s>%s (%.03f %%)</%s>', $curr['amount'] / 1024, $curr['statusType'], $curr['statusDescription'], $curr['diffPercentage'], $curr['statusType']));
                $output->writeln('');

                // Unset variables to prevent instable memory usage
                unset($peak);
                unset($curr);
            });
        }

        $signalHandler = function($signo) use ($factory, &$children, $id) {
            foreach ($children as $pid => $child) {
                pcntl_waitpid($pid, $status);
            }

            $factory->unregisterWorker($id);

            exit;
        };
        pcntl_signal(SIGINT, $signalHandler);
        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGHUP,  $signalHandler);
        pcntl_signal(SIGUSR1, $signalHandler);

        $loop->run();
    }

    /**
     * Guess logger level
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
            case OutputInterface::VERBOSITY_NORMAL:
                $level = Logger::WARNING;
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
            default:
                $level = Logger::WARNING;
                break;
        }

        return $level;
    }

    /**
     * Get welcome message
     *
     * @return string
     */
    protected function getWelcome()
    {
        return strtr("           _                       
 ___ _   _| |____      ____ _ _   _     |
/ __| | | | '_ \ \ /\ / / _` | | | |    |    Host: %host%
\__ \ |_| | |_) \ V  V / (_| | |_| |    |    PID : %pid%
|___/\__,_|_.__/ \_/\_/ \__,_|\__, |    |    Date: %date%
           <info>%version%</info> |___/     |
", array(
    '%version%' => str_pad('v'.ltrim($this->getApplication()->getVersion(), 'v'), 18, ' ', STR_PAD_LEFT),
    '%host%'    => gethostname(),
    '%pid%'     => getmypid(),
    '%date%'    => date('c')
));
    }
}
