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

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Config aware command
 */
abstract class ConfigAwareCommand extends ContainerAwareCommand
{
    const OPTION_PREFIX = 'config-';

    /**
     * Configure input definition
     */
    public function configureInputDefinition()
    {
        $config = $this->get('config');
        $tree   = $config->getConfigTreeBuilder();
        foreach ($tree->buildTree()->getChildren() as $child) {
            $this->addOption(self::OPTION_PREFIX.$child->getName(), null, InputOption::VALUE_REQUIRED, $child->getInfo(), $config->get($child->getName()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processConfiguration(InputInterface $input, OutputInterface $output)
    {
        $config = $this->get('config');
        foreach ($config->all() as $key => $val) {
            $optionKey = '--'.self::OPTION_PREFIX.$key;
            if ($optionVal = $input->getParameterOption($optionKey)) {
                $config->set($key, $optionVal);
            }
        }
    }
}
