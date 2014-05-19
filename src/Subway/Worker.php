<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway;

use Subway\Job;
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
     * @param  array $job
     * @return bool
     */
    public function perform(array $job)
    {
        if (!class_exists($job['class'])) {
            throw new SubwayException('Could not find job class ' . $job['class']);
        }

        if (!method_exists($job['class'], 'perform')) {
            throw new SubwayException('Job class ' . $job['class'] . ' does not contain a perform method.');
        }

        $instance = new $job['class'];
        $logger = $this->factory->getLogger();

        try {
            $this->factory->updateStatus($job['id'], Job::STATUS_RUNNING);
            $instance->perform($job['args']);
            $this->factory->updateStatus($job['id'], Job::STATUS_COMPLETE);

            $this->factory->getRedis()->incrby('resque:stat:processed', 1);
            $this->factory->getRedis()->incrby('resque:stat:processed:'.$this->id, 1);

            if ($logger) {
                $logger->addNotice(sprintf('[%s][%s] Job finised successfully. Mem: %sMB', date('Y-m-d\TH:i:s'), $job['id'], round(memory_get_peak_usage() / 1024 / 1024, 2)));
            }
        } catch (\Exception $e) {
            $this->factory->updateStatus($job['id'], Job::STATUS_FAILED);
            $this->factory->getRedis()->incrby('resque:stat:failed', 1);
            $this->factory->getRedis()->incrby('resque:stat:failed:'.$this->id, 1);
            $this->factory->getRedis()->rpush('resque:failed', json_encode(array(
                'failed_at' => strftime('%a %b %d %H:%M:%S %Z %Y'),
                'payload'   => $job,
                'exception' => get_class($e),
                'error'     => $e->getMessage(),
                'backtrace' => explode("\n", $e->getTraceAsString()),
                'worker'    => $this->id,
                'queue'     => $job['queue'],
            )));
            
            if ($logger) {
                $logger->addError(sprintf('[%s][%s] Job execution failed. %s:%s', date('Y-m-d\TH:i:s'), $job['id'], get_class($e), $e->getMessage()));
            }

            return false;
        }

        return true;
    }
}