<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway;

use Subway\Command as Commands;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Symfony console application class
 */
class Application extends BaseApplication
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new Commands\WorkerCommand();
        $defaultCommands[] = new Commands\StatusCommand();
        $defaultCommands[] = new Commands\ClearCommand();
        $defaultCommands[] = new Commands\SelfUpdateCommand();

        return $defaultCommands;
    }

    /**
     * Get logo
     * 
     * @return string
     */
    public function getLogo()
    {
        return "           _                       
 ___ _   _| |____      ____ _ _   _ 
/ __| | | | '_ \ \ /\ / / _` | | | |
\__ \ |_| | |_) \ V  V / (_| | |_| |
|___/\__,_|_.__/ \_/\_/ \__,_|\__, |
                              |___/ 
\n";
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return $this->getLogo() . parent::getHelp();
    }
}