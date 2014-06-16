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

use Subway\Config;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Init command
 */
class InitCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Create a new subway configuration file')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force creation (Overwrites existing configuration)')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ((bool) $input->getOption('force') === false && file_exists('subway.yml') === true) {
            return $output->writeln('<error> Subway configuration file subway.yml already exists! </error>');
        }

        $dumped = '';
        $dumper = new YamlReferenceDumper();
        $config = new Config();
        $node   = $config->getConfigTreeBuilder()->buildTree();
        foreach ($node->getChildren() as $child) {
            $dumped .= $dumper->dumpNode($child);
        }
        $dumped = trim($dumped);
        
        file_put_contents('subway.yml', $dumped);
        $output->writeln('<info>Subway configuration file subway.yml created successfully!</info>');
    }
}
