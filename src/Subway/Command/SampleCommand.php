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
use Subway\Message;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sample command
 */
class SampleCommand extends RedisAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sample')
            ->setDescription('Loads sample jobs')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Sample job count', 1)
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Sample job queue', 'sample')
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = new Factory($this->redis);

        for ($i = 0; $i < intval($input->getOption('count')); $i++) {
            $message = new Message($input->getOption('queue'), 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
            $id = $factory->enqueue($message);
            $output->writeln(sprintf('Job %s enqueued in %s', $message->getId(), $message->getQueue()));
        }
    }
}
