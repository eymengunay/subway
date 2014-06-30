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

use Subway\Exception\SubwayException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Status command
 */
class StatusCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Show subway status')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'To output help in other formats', 'txt')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formats = array('txt', 'json');
        if (in_array($input->getOption('format'), $formats) === false) {
            throw new SubwayException(sprintf('Unsupported format "%s".', $input->getOption('format')));
        }

        $queue  = $this->queueStatus($input, $output);
        $worker = $this->workerStatus($input, $output);

        switch ($input->getOption('format')) {
            case 'json':
                $outstr = json_encode(array('queue' => $queue, 'worker' => $worker), JSON_PRETTY_PRINT);
                $output->writeln($outstr);
                break;
            case 'txt':
            default:
                $output->writeln(sprintf('<info>Queues (%s)</info>', count($queue)));
                foreach ($queue as $key => $val) {
                    $output->writeln(sprintf('  * %s: %s', ucfirst($key), $val));
                }

                $output->writeln(sprintf('<info>Workers (%s)</info>', count($worker)));
                foreach ($worker as $val) {
                    $output->writeln(sprintf('  * Host: %s PID: %s', $val['host'], $val['pid']));
                }
                break;
        }
    }

    /**
     * Queue status
     * 
     * @return array
     */
    protected function queueStatus()
    {
        $rows = array();

        foreach ($this->get('factory')->getQueues() as $queue) {
            $rows[$queue->getName()] = $queue->count();
        }

        $delayedQueue   = $this->get('factory')->getDelayedQueue();
        $repeatingQueue = $this->get('factory')->getRepeatingQueue();

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

        foreach ($this->get('factory')->getWorkers() as $worker) {
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
