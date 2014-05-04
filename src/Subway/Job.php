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

/**
 * Job class
 */
abstract class Job
{
    /**
     * Perform job
     * 
     * @param array $args 
     */
    abstract public function perform($args);
}