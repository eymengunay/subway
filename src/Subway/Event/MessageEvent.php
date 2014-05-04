<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Message event
 */
class MessageEvent extends Event
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $args;

    /**
     * Class constructor
     * 
     * @param string $id
     * @param string $class
     * @param array  $args
     */
    public function __construct($id, $class, $args = array())
    {
        $this->id    = $id;
        $this->class = $class;
        $this->args  = $args;
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
     * Get class
     * 
     * @return string
     */
    public function getClass()
    {
        return $this->class;
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
}