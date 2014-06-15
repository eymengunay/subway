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

use Pimple;
use Symfony\Component\Console\Command\Command;

/**
 * Container aware command
 */
abstract class ContainerAwareCommand extends Command
{
    /**
     * Get container
     * 
     * @return Pimple
     */
    public function getContainer()
    {
        return $this->getApplication()->getContainer();
    }

    /**
     * Container service setter shortcut
     *
     * @param  string $id
     * @param  object $service
     * @return self
     */
    public function setService($id, $service)
    {
        $container = $this->getContainer();
        $container[$id] = function() use ($service) {
            return $service;
        };

        return $this;
    }

    /**
     * Container getter shortcut
     * 
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        $container = $this->getContainer();

        return $container[$key];
    }
}
