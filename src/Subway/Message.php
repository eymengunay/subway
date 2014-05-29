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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Message
 */
class Message implements \JsonSerializable
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
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = array(
            'id'    => $this->getId(),
            'queue' => $this->getQueue(),
            'class' => $this->getClass(),
            'args'  => $this->getArgs()->toArray()
        );

        if ($at = $this->getAt()) {
            $data['at'] = $at->format('c');
        }

        if ($interval = $this->getInterval()) {
            $data['interval'] = $interval;
        }

        return $data;
    }

    /**
     * Json unserialize
     * 
     * @param  string  $str
     * @return Message
     */
    public static function jsonUnserialize($str)
    {
        $array = json_decode($str, true);
        $instance = new self($array['queue'], $array['class'], $array['args']);

        if (array_key_exists('id', $array)) {
            $instance->setId($array['id']);
        }

        if (array_key_exists('at', $array)) {
            $instance->setAt(new \DateTime($array['at']));
        }

        if (array_key_exists('interval', $array)) {
            $instance->setInterval($array['interval']);
        }

        return $instance;
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

        if (!method_exists($class, 'perform')) {
            throw new SubwayException("Job class $class does not contain a perform method");
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
     * @return array
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