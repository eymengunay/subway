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

use Subway\Factory;
use Subway\Exception\SubwayException;

/**
 * Worker class
 */
class Worker
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * Class constructor
     *
     * @param string  $id
     * @param Factory $factory
     */
    public function __construct($id, Factory $factory)
    {
        $this->id      = $id;
        $this->factory = $factory;
    }

    /**
     * Perform job
     *
     * @param  array   $job
     * @param  closure $onSuccess
     * @param  closure $onFailure
     * @return bool
     */
    public function perform(array $job, $onSuccess = null, $onFailure = null)
    {
        if (!class_exists($job['class'])) {
            throw new SubwayException('Could not find job class ' . $job['class']);
        }

        if (!method_exists($job['class'], 'perform')) {
            throw new SubwayException('Job class ' . $job['class'] . ' does not contain a perform method.');
        }

        $instance = new $job['class'];

        try {
            $this->factory->updateStatus($job['id'], Factory::STATUS_RUNNING);
            $instance->perform($job['args']);
            $this->factory->updateStatus($job['id'], Factory::STATUS_COMPLETE);

            $this->factory->getRedis()->incrby('resque:stat:processed', 1);
            $this->factory->getRedis()->incrby('resque:stat:processed:'.$this->id, 1);
        } catch (\Exception $e) {
            $this->factory->updateStatus($job['id'], Factory::STATUS_FAILED);
            $this->factory->getRedis()->incrby('resque:stat:failed', 1);
            $this->factory->getRedis()->incrby('resque:stat:failed:'.$this->id, 1);

            return false;
        }

        return true;
    }
}