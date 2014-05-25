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
     * @param  Message $message
     * @return bool
     */
    public function perform(Message $message)
    {
        if (!class_exists($message->getClass())) {
            throw new SubwayException('Could not find job class ' . $message->getClass());
        }

        if (!method_exists($message->getClass(), 'perform')) {
            throw new SubwayException('Job class ' . $message->getClass() . ' does not contain a perform method.');
        }

        $class = $message->getClass();
        $instance = new $class;
        $instance->setMessage($message);

        try {
            $this->factory->getLogger()->addNotice(sprintf('[%s] Starting job', $message->getId()));

            $this->factory->updateStatus($message->getId(), Job::STATUS_RUNNING);
            $instance->perform($message->getArgs());
            $this->factory->updateStatus($message->getId(), Job::STATUS_COMPLETE);

            $this->factory->getRedis()->incrby('resque:stat:processed', 1);
            $this->factory->getRedis()->incrby('resque:stat:processed:'.$this->id, 1);

            $this->factory->getLogger()->addNotice(sprintf('[%s] Job finised successfully', $message->getId()));
        } catch (\Exception $e) {
            $this->exceptionHandler($e, $message);

            return false;
        }

        return true;
    }

    /**
     * Exception handler
     * 
     * @param  \Exception $e
     * @param  Message   $message
     */
    protected function exceptionHandler(\Exception $e, Message $message)
    {
        $this->factory->updateStatus($message->getId(), Job::STATUS_FAILED);
        $this->factory->getRedis()->incrby('resque:stat:failed', 1);
        $this->factory->getRedis()->incrby('resque:stat:failed:'.$this->id, 1);
        $this->factory->getRedis()->rpush('resque:failed', json_encode(array(
            'failed_at' => strftime('%a %b %d %H:%M:%S %Z %Y'),
            'payload'   => json_encode($message),
            'exception' => get_class($e),
            'error'     => $e->getMessage(),
            'backtrace' => explode("\n", $e->getTraceAsString()),
            'worker'    => $this->id,
            'queue'     => $message->getQueue(),
        )));
        
        $this->factory->getLogger()->addError(sprintf('[%s][%s] Job execution failed. %s:%s', date('Y-m-d\TH:i:s'), $message->getId(), get_class($e), $e->getMessage()));
    }
}