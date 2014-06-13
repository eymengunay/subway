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

use Subway\Queue;
use Subway\Worker;
use Subway\Exception\SubwayException;
use React\EventLoop\Factory as React;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Worker command
 */
class WorkerCommand extends ConfigAwareCommand
{
    /**
     * @var string
     */
    protected $id;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('worker')
            ->setDescription('Starts a subway worker')
            ->addArgument('queues', InputArgument::IS_ARRAY, 'Queue names (separate using space)', array())
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        // Register worker
        $this->id = gethostname() . ':' . getmypid() . ':' . implode(',', $input->getArgument('queues') ?: array('*'));
        $this->getFactory()->registerWorker($this->id);
        $this->getLogger()->addInfo("Worker $this->id is ready");
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
        $loop->addPeriodicTimer($this->getConfig()->get('interval'), $this->queueTimer($input, $output, $children));
        $loop->addPeriodicTimer($this->getConfig()->get('interval'), $this->delayedTimer($output));
        $loop->addPeriodicTimer($this->getConfig()->get('interval'), $this->repeatingTimer($output));

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
                $queues = $this->getFactory()->getQueues($input->getArgument('queues'));
            } catch (\Exception $e) {
                $this->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                throw $e;
            }

            foreach ($queues as $queue) {
                // Check max concurrent limit
                if ($children->count() >= $this->getConfig()->get('concurrent')) {
                    $this->getLogger()->addInfo('Max concurrent limit of '.$this->getConfig()->get('concurrent').' reached');
                    break;
                }

                // Pop queue
                try {
                    $message = $queue->pop();
                } catch (\Exception $e) {
                    $this->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

                    throw $e;
                }

                if (!$message) {
                    continue;
                }

                // Get job instance
                try {
                    $job = $message->getJobInstance();
                } catch (SubwayException $e) {
                    $output->writeln(sprintf('<error>[%s][%s] Message error: %s </error>', date('Y-m-d\TH:i:s'), substr($message->getId(), 0, 7), $e->getMessage()));
                    continue;
                }

                $pid = pcntl_fork();
                if ($pid == -1) {
                    // Wtf?
                    $this->getLogger()->addError('Could not fork');
                    throw new SubwayException('Could not fork');
                } else if ($pid) {
                    // Parent process
                    $children->set($pid, time());
                    $output->writeln(sprintf('[%s][%s] Starting job %s. Pid: %s Queue: %s', date('Y-m-d\TH:i:s'), substr($message->getId(), 0, 7), $job->getName(), $pid, $message->getQueue()));
                } else {
                    // Reconnect to redis
                    $redis = $this->getRedis();
                    if ($redis->isConnected()) {
                        $redis->disconnect();
                    }
                    $redis->connect();

                    // Child process
                    $worker = new Worker($this->id, $this->getFactory());
                    if ($worker->perform($job)) {
                        $output->writeln(sprintf('<info>[%s][%s] Job %s finished successfully. Mem: %sMB</info>', date('Y-m-d\TH:i:s'), substr($message->getId(), 0, 7), $job->getName(), round(memory_get_peak_usage() / 1024 / 1024, 2)));
                    } else {
                        $output->writeln(sprintf('<error>[%s][%s] Job %s execution failed.</error>', date('Y-m-d\TH:i:s'), substr($message->getId(), 0, 7), $job->getName()));
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
            $delayedQueue = $this->getFactory()->getDelayedQueue();
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
            $repeatingQueue = $this->getFactory()->getRepeatingQueue();
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
            $this->getLogger()->addError(sprintf('Uncaught exception. Code: %s Message: %s', $e->getCode(), $e->getMessage()));

            throw $e;
        }

        if ($message) {
            // Remove at & interval
            $message
                ->setAt(null)
                ->setInterval(null)
            ;
            $id = $this->getFactory()->enqueue($message);

            $name = ucfirst($queue->getName());
            $this->getLogger()->addNotice(sprintf('[%s][%s] %s job enqueued in %s.', date('Y-m-d\TH:i:s'), $message->getId(), $name, $message->getQueue()));
            $output->writeln(sprintf('<comment>[%s][%s] %s job enqueued in %s.</comment>', date('Y-m-d\TH:i:s'), substr($id, 0, 7), $name, $message->getQueue()));
        }
    }

    /**
     * Install signal handlers
     * 
     * @param ArrayCollection $children
     */
    protected function installSignalHandlers(ArrayCollection $children)
    {
        $signalHandler = function() use ($children) {
            foreach ($children as $pid => $child) {
                pcntl_waitpid($pid, $status);
            }

            $this->getFactory()->unregisterWorker($this->id);

            exit;
        };
        pcntl_signal(SIGINT, $signalHandler);
        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGHUP,  $signalHandler);
        pcntl_signal(SIGUSR1, $signalHandler);
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
