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

use Subway\Message;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sample command
 */
class SampleCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sample')
            ->setDescription('Loads sample jobs')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Sample job count', 1)
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Sample job queue', 'sample')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        for ($i = 0; $i < intval($input->getOption('count')); $i++) {
            $message = new Message($input->getOption('queue'), 'Subway\Tests\Job\Md5Job', array(
                'hello' => 'world'
            ));
            $this->get('factory')->enqueue($message);
            $output->writeln(sprintf('<info>Job %s enqueued in %s</info>', $message->getId(), $message->getQueue()));
        }
    }
}
