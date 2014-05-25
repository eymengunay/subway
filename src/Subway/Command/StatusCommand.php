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
use React\EventLoop\Factory as React;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Status command
 */
class StatusCommand extends RedisAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Show subway status')
            ->addOption('live', 'l', InputOption::VALUE_NONE, 'Live status')
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        if ( (bool)$input->getOption('live') ) {
            declare(ticks=1);
            $loop = React::create();

            $loop->addPeriodicTimer(1, function () use ($input, $output) {
                $status = $this->status($input, $output);
                $output->writeln(json_encode($status, JSON_PRETTY_PRINT));
            });

            $loop->run();
        } else {
            $output->writeln(json_encode($this->status($input, $output), JSON_PRETTY_PRINT));
        }
    }

    /**
     * Status
     * 
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array
     */
    protected function status(InputInterface $input, OutputInterface $output)
    {
        return array(
            'queue' => $this->queueStatus($input, $output),
            'worker' => $this->workerStatus($input, $output)
        );
    }

    /**
     * Queue status
     * 
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array
     */
    protected function queueStatus(InputInterface $input, OutputInterface $output)
    {
        $rows = array();

        $factory = new Factory($this->redis);
        foreach ($factory->getQueues() as $queue) {
            $rows[$queue->getName()] = $queue->count();
        }

        $delayedQueue   = $factory->getDelayedQueue();
        $repeatingQueue = $factory->getRepeatingQueue();

        if ($delayedCount = $delayedQueue->count()) {
            $rows[$delayedQueue->getName()] = $delayedCount;
        }

        if ($repeatingCount = $repeatingQueue->count()) {
            $rows[$repeatingQueue->getName()] = $repeatingCount;
        }

        return $rows;
    }

    /**
     * Worker status
     * 
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array
     */
    protected function workerStatus(InputInterface $input, OutputInterface $output)
    {
        $rows = array();

        $factory = new Factory($this->redis);
        foreach ($factory->getWorkers() as $worker) {
            list($host, $pid, $queues) = explode(':', $worker);
            $rows[] = array(
                'host'   => $host,
                'pid'    => $pid,
                'queues' => $queues,
            );
        }

        return $rows;
    }
}
