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
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rows = array();

        $factory = new Factory($this->redis);
        foreach ($factory->getQueues() as $queue) {
            $rows[] = array(
                $queue->getName(),
                $queue->count()
            );
        }

        $delayedQueue   = $factory->getDelayedQueue();
        $repeatingQueue = $factory->getRepeatingQueue();
        $rows[] = array($delayedQueue->getName(), $delayedQueue->count());
        $rows[] = array($repeatingQueue->getName(), $repeatingQueue->count());

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Queue', 'Jobs'))
            ->setRows($rows)
        ;
        $table->render($output);
    }
}
