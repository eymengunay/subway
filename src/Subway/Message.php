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

use Subway\Exception\SubwayException;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Message
 */
class Message
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var DateTime
     */
    protected $at;

    /**
     * @var string
     */
    protected $interval;

    /**
     * Class constructor
     *
     * @param string $queue
     * @param string $class
     * @param array  $args
     */
    public function __construct($queue, $class, array $args = array())
    {
        $this->queue = strval($queue);
        $this->class = strval($class);
        $this->args  = new ArrayCollection($args);
        $this->id    = sha1(uniqid($this->getHash(), true));

        return $this;
    }

    /**
     * Get job instance
     * 
     * @return Job
     */
    public function getJobInstance()
    {
        $class = $this->getClass();
        if (!class_exists($class)) {
            throw new SubwayException("Could not find job class $class");
        }

        if (is_subclass_of($class, 'Subway\Job') === false) {
            throw new SubwayException("Job class \"$class\" must be an instance of \"Subway\Job\"");
        }

        $instance = new $class;
        $instance->setMessage($this);

        return $instance;
    }

    /**
     * Calculate hash
     * 
     * @return string
     */
    public function getHash()
    {
        $json = json_encode(array(
            $this->getQueue(),
            $this->getClass(),
            $this->getArgs(),
        ));

        return sha1($json);
    }

    /**
     * Set id
     *
     * @param  string
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     * 
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get queue
     * 
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get class
     * 
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set args
     *
     * @param  array $args
     * @return self
     */
    public function setArgs(array $args = array())
    {
        $this->args = new ArrayCollection($args);

        return $this;
    }

    /**
     * Get args
     * 
     * @return ArrayCollection
     */
    public function getArgs()
    {
        return $this->args;   
    }

    /**
     * Set at
     * 
     * @param  \DateTime $at
     * @return self
     */
    public function setAt(\DateTime $at = null)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * Get at
     * 
     * @return DateTime 
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * Set interval
     *
     * @param  string $interval
     * @return self
     */
    public function setInterval($interval = null)
    {
        if (is_null($interval) === false) {
            // Repeating jobs need both at & interval
            if (is_null($this->getAt())) {
                $this->setAt(new \DateTime());
            }
        }

        $this->interval = $interval;

        return $this;
    }

    /**
     * Get interval
     * 
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }
}