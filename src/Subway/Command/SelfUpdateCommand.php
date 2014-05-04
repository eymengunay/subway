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

use Herrera\Phar\Update\Manager;
use Herrera\Json\Exception\FileException;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Self update command
 */
class SelfUpdateCommand extends Command
{
    const MANIFEST_FILE = 'http://eymengunay.github.io/subway/manifest.json';

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(array('selfupdate'))
            ->setDescription('Updates subway to the latest version')
            ->addOption('major', null, InputOption::VALUE_NONE, 'Allow major version update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Looking for updates...');

        try {
            $manager = new Manager(Manifest::loadFile(self::MANIFEST_FILE));
        } catch (FileException $e) {
            $output->writeln('<error>Unable to search for updates</error>');

            return 1;
        }

        $currentVersion = ltrim($this->getApplication()->getVersion(), 'v');
        $allowMajor = $input->getOption('major');

        if ($manager->update($currentVersion, $allowMajor)) {
            $output->writeln('<info>Updated to latest version</info>');
        } else {
            $output->writeln('<comment>Already up-to-date</comment>');
        }
    }
}
