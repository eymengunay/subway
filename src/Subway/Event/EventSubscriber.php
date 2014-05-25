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

use Subway\Factory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * Class constructor
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array();
    }
}