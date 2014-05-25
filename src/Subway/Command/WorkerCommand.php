<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
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
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
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
     * @var string
     */
    protected $id;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var Logger
     */
    protected $logger;

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
            ->addOption('concurrent', 'c', InputOption::VALUE_REQUIRED, 'Max concurrent job count', 5)
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

        // Current working dir
        if ($cwd = $input->getOption('cwd')) {
            chdir(realpath($cwd));
        }

        // Autoloader
        $autoload = $input->getOption('autoload');
        if (file_exists($autoload) === false) {
            throw new SubwayException('Autoload file not found');
        }
        require_once $autoload;

        // Logger
        $this->logger = new Logger('subway');
        $this->logger->pushProcessor(new MemoryPeakUsageProcessor());
        $this->logger->pushHandler(new RotatingFileHandler($input->getOption('log'), 0, $this->guessLoggerLevel($output)));

        // Factory
        $this->factory = new Factory($this->redis);
        $this->factory->setLogger($this->logger);

        // Register worker
        $this->id = gethostname() . ':'.getmypid() . ':' . implode(',', $input->getArgument('queues') ?: array('*'));
        $this->factory->registerWorker($this->id);
        $this->logger->addInfo("Worker $this->id is ready");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getWelcome());

        $children = new ArrayCollection();
        $this->installSignalHandlers($children);

        $loop = $this->createLoop($input, $output);
        $loop->addPeriodicTimer($input->getOption('interval'), $this->queueTimer($input, $output, $children));
        $loop->addPeriodicTimer($input->getOption('interval'), $this->delayedTimer($output));
        $loop->addPeriodicTimer($input->getOption('interval'), $this->repeatingTimer($output));

        if ( (bool)$input->getOption('detect-leaks') ) {
            $loop->addPeriodicTimer($input->getOption('interval'), $this->leakTimer($output));
        }

        $loop->run();
    }

    /**
     * Create loop
     * 
     * @return mixed
     */
    protected function createLoop()
    {
        declare(ticks=1);
        
        return React::create();
    }

    /**
     * Queue timer
     * 
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  ArrayCollection $children
     * @return closure
     */
    protected function queueTimer(InputInterface $input, OutputInterface $output, ArrayCollection $children)
    {
        return function () use ($input, $output, $children) {
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
                $queues = $this->factory->getQueues($input->getArgument('queues'));
            } catch (\Exception $e) {
                $this->logger->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                throw $e;
            }

            foreach ($queues as $queue) {
                // Check max concurrent limit
                if ($children->count() >= $input->getOption('concurrent')) {
                    $this->logger->addInfo('Max concurrent limit of '.$input->getOption('concurrent').' reached');
                    break;
                }

                // Pop queue
                try {
                    $message = $queue->pop();
                } catch (\Exception $e) {
                    $this->logger->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                    throw $e;
                }

                if (!$message) {
                    continue;
                }

                $pid = pcntl_fork();
                if ($pid == -1) {
                    // Wtf?
                    $this->logger->addError('Could not fork');
                    throw new SubwayException('Could not fork');
                } else if ($pid) {
                    // Parent process
                    $children->set($pid, time());
                    $output->writeln(sprintf('[%s][%s] Starting job. Pid: %s', date('Y-m-d\TH:i:s'), substr($message->getId(), 0, 7), $pid));
                } else {
                    // Reconnect to redis
                    $redis = $this->factory->getRedis();
                    if ($redis->isConnected()) {
                        $redis->disconnect();
                    }
                    $redis->connect();

                    // Child process
                    $worker = new Worker($this->id, $this->factory);
                    if ($worker->perform($message)) {
                        $output->writeln(sprintf('<info>[%s][%s] Finised successfully. Mem: %sMB</info>', date('Y-m-d\TH:i:s'), substr($message->getId(), 0, 7), round(memory_get_peak_usage() / 1024 / 1024, 2)));
                    } else {
                        $output->writeln(sprintf('<error>[%s][%s] Job execution failed.</error>', date('Y-m-d\TH:i:s'), substr($message->getId(), 0, 7)));
                    }

                    posix_kill(getmypid(), 9);
                }
            }
        };
    }

    /**
     * Delayed timer
     * 
     * @param  OutputInterface $output
     * @return closure
     */
    protected function delayedTimer(OutputInterface $output)
    {
        return function () use ($output) {
            $delayedQueue = $this->factory->getDelayedQueue();
            $this->handleScheduled($output, $delayedQueue);
        };
    }

    /**
     * Repeating timer
     * 
     * @param  OutputInterface $output
     * @return closure
     */
    protected function repeatingTimer(OutputInterface $output)
    {
        return function () use ($output) {
            $repeatingQueue = $this->factory->getRepeatingQueue();
            $this->handleScheduled($output, $repeatingQueue);
        };
    }

    /**
     * Handle scheduled
     * 
     * @param OutputInterface $output
     * @param Queue           $queue
     */
    protected function handleScheduled(OutputInterface $output, Queue $queue)
    {
        if ($queue->count() < 1) {
            return;
        }
        // Pop queue
        try {
            $message = $queue->pop();
        } catch (\Exception $e) {
            $this->factory->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

            throw $e;
        }

        if ($message) {
            // Remove at & interval
            $message
                ->setAt(null)
                ->setInterval(null)
            ;
            $id = $this->factory->enqueue($message);

            $name = ucfirst($queue->getName());
            $this->factory->getLogger()->addNotice(sprintf('[%s][%s] %s job enqueued in %s.', date('Y-m-d\TH:i:s'), $message->getId(), $name, $message->getQueue()));
            $output->writeln(sprintf('<comment>[%s][%s] %s job enqueued in %s.</comment>', date('Y-m-d\TH:i:s'), substr($id, 0, 7), $name, $message->getQueue()));
        }
    }

    /**
     * Leak timer
     * 
     * @param  OutputInterface $output
     * @return closure
     */
    protected function leakTimer(OutputInterface $output)
    {
        $lastPeakUsage = 0;
        $lastUsage = 0;
        $memInfo = function($peak) use (&$lastPeakUsage, &$lastUsage) {
            $lastUsage                 = ($peak) ? $lastPeakUsage : $lastUsage;
            $info                      = array();
            $info['amount']            = ($peak) ? memory_get_peak_usage() : memory_get_usage();
            $info['diff']              = $info['amount'] - $lastUsage;
            $info['diffPercentage']    = ($lastUsage == 0) ? 0 : abs($info['diff'] / ($lastUsage / 100));
            $info['statusDescription'] = '=';
            $info['statusType']        = 'info';

            if ($info['diff'] > 0) {
                $info['statusDescription'] = '+';
                $info['statusType']        = 'error';
            } else if ($info['diff'] < 0) {
                $info['statusDescription'] = '-';
                $info['statusType']        = 'comment';
            }

            // Update last usage variables
            if ($peak) {
                $lastPeakUsage = $info['amount'];
            } else {
                $lastUsage     = $info['amount'];
            }

            return $info;
        };

        return function () use ($output, $memInfo) {
            // Gather memory info
            $peak = $memInfo(true);
            $curr = $memInfo(false);
            
            $prefix  = sprintf('[%s][meminfo]', date('Y-m-d\TH:i:s'));
            $peak    = sprintf('Peak: %.02fKB <%s>%s(%.03f) %%</%s>', $peak['amount'] / 1024, $peak['statusType'], $peak['statusDescription'], $peak['diffPercentage'], $peak['statusType']);
            $current = sprintf('Current: %.02fKB <%s>%s(%.03f) %%</%s>', $curr['amount'] / 1024, $curr['statusType'], $curr['statusDescription'], $curr['diffPercentage'], $curr['statusType']);
            
            $output->writeln("<fg=cyan>$prefix $peak $current</fg=cyan>");

            // Unset variables to prevent instable memory usage
            unset($peak);
            unset($curr);
        };
    }

    /**
     * Install signal handlers
     * 
     * @param ArrayCollection $children
     */
    protected function installSignalHandlers(ArrayCollection $children)
    {
        $signalHandler = function($signo) use ($children) {
            foreach ($children as $pid => $child) {
                pcntl_waitpid($pid, $status);
            }

            $this->factory->unregisterWorker($this->id);

            exit;
        };
        pcntl_signal(SIGINT, $signalHandler);
        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGHUP,  $signalHandler);
        pcntl_signal(SIGUSR1, $signalHandler);
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
