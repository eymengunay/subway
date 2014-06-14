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

use React\EventLoop\Factory as React;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Status command
 */
class StatusCommand extends ConfigAwareCommand
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
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        $output->writeln(json_encode(array(
            'queue'  => $this->queueStatus($input, $output),
            'worker' => $this->workerStatus($input, $output)
        ), JSON_PRETTY_PRINT));
    }

    /**
     * Queue status
     * 
     * @return array
     */
    protected function queueStatus()
    {
        $rows = array();

        foreach ($this->getFactory()->getQueues() as $queue) {
            $rows[$queue->getName()] = $queue->count();
        }

        $delayedQueue   = $this->getFactory()->getDelayedQueue();
        $repeatingQueue = $this->getFactory()->getRepeatingQueue();

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
     * @return array
     */
    protected function workerStatus()
    {
        $rows = array();

        foreach ($this->getFactory()->getWorkers() as $worker) {
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
