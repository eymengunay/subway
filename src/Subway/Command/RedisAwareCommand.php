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

use Predis\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Redis aware command
 */
abstract class RedisAwareCommand extends Command
{
    /**
     * @var Client
     */
    protected $redis;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            // Redis config
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Redis host', '127.0.0.1:6379')
            ->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'Redis prefix')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->redis = new Client(sprintf('tcp://%s', $input->getOption('host')), array('prefix' => $input->getOption('prefix')));
    }
}
