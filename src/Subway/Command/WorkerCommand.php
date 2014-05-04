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

            // Autoload 
            ->addOption('cwd', 'w', InputOption::VALUE_OPTIONAL, 'Current working directory')

            // Autoload 
            ->addOption('autoload', 'a', InputOption::VALUE_REQUIRED, 'Application autoloader', './vendor/autoload.php')

            // Queues
            ->addArgument('queues', InputArgument::IS_ARRAY, 'Queue names (separate using space)', array())

            // Interval
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'How often to check for new jobs across the queues', 5)

            // Concurrency
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Max concurrent fork count', 1)
            
            // Redis config
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Redis host', '127.0.0.1:6379')
            ->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'Redis prefix')
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

        $id = gethostname() . ':'.getmypid() . ':' . implode(',', $input->getArgument('queues') ?: array('all'));
        $factory->registerWorker($id);

        declare(ticks=1);
        $loop = React::create();

        // Execute timer
        $timer = $loop->addPeriodicTimer($input->getOption('interval'), function ($timer) use ($id, $input, $output, $factory, $children) {
            foreach ($factory->getQueues($input->getArgument('queues')) as $queue) {
                // Check max concurrent limit
                if ($children->count() >= $input->getOption('count')) {
                    continue;
                }

                // Pop queue
                if (!$job = $queue->pop()) {
                    continue;
                }

                $pid = pcntl_fork();
                if ($pid == -1) {
                    // Wtf?
                    throw new SubwayException('Could not fork');
                } else if ($pid) {
                    // Parent process
                    $children->set($pid, time());
                    $output->writeln(sprintf('[%s][%s] Starting job. Pid: %s', date('Y-m-d\TH:i:s'), substr($job['id'], 0, 7), $pid));
                } else {
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

        // Worker cleanup timer
        $timer = $loop->addPeriodicTimer(1, function ($timer) use ($input, $output, $factory, $children) {
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
        });

        // Delayed timer
        $timer = $loop->addPeriodicTimer($input->getOption('interval'), function ($timer) use ($input, $output, $factory) {
            $delayedQueue = $factory->getDelayedQueue();
            $delayedJob   = $delayedQueue->pop();
            if ($delayedJob) {
                $id = $factory->enqueue($delayedJob['queue'], $delayedJob['class'], $delayedJob['args']);

                $output->writeln(sprintf('<comment>[%s][%s] Delayed job enqueued in %s.</comment>', date('Y-m-d\TH:i:s'), substr($id, 0, 7), $delayedJob['queue']));
            }
        });

        // Repeating timer
        $timer = $loop->addPeriodicTimer($input->getOption('interval'), function ($timer) use ($input, $output, $factory) {
            $repeatingQueue = $factory->getRepeatingQueue();
            $repeatingJob   = $repeatingQueue->pop();
            if ($repeatingJob) {
                $id = $factory->enqueue($repeatingJob['queue'], $repeatingJob['class'], $repeatingJob['args']);

                $output->writeln(sprintf('<comment>[%s][%s] Repeating job enqueued in %s.</comment>', date('Y-m-d\TH:i:s'), substr($id, 0, 7), $repeatingJob['queue']));
            }
        });

        $signalHandler = function($signo) use ($factory, &$children, $id) {
            foreach ($children as $pid => $worker) {
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
    '%version%' => str_pad('v'.$this->getApplication()->getVersion(), 18, ' ', STR_PAD_LEFT),
    '%host%'    => gethostname(),
    '%pid%'     => getmypid(),
    '%date%'    => date('c')
));
    }
}
