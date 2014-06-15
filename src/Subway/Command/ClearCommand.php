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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear command
 */
class ClearCommand extends ConfigAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('clear')
            ->setDescription('Clear subway database')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('no-interaction') === false) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                    $output,
                    '<question>This action will erase entire subway database. Are you sure you want to continue? (y/N)</question>',
                    false
                )) {
                
                return $output->writeln('<comment>Command aborted by user</comment>');
            }
        }

        $this->get('factory')->clear();
        $output->writeln('<info>Database cleared successfully</info>');
    }
}
