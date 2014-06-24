<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Status event
 */
class StatusEvent extends Event
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var integer
     */
    protected $status;

    /**
     * Class constructor
     *
     * @param string  $id
     * @param integer $status
     */
    public function __construct($id, $status)
    {
        $this->id     = $id;
        $this->status = $status;
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
     * Get status
     * 
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}