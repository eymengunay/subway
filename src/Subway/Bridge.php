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
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Bridge
 */
abstract class Bridge implements EventSubscriberInterface
{
    /**
     * @var ArrayCollection
     */
    protected $options;

    /**
     * Class constructor
     */
    public function __construct($options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = new ArrayCollection($resolver->resolve($options));

        $this->initialize();
    }

    /**
     * Initialize bridge
     */
    abstract public function initialize();

    /**
     * Configure bridge options
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array();
    }

    /**
     * Get event dispatcher
     * 
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return new EventDispatcher();
    }

    /**
     * Get logger
     * 
     * @return Logger
     */
    public function getLogger($level = Logger::WARNING)
    {
        if (file_exists('logs') === false) {
            mkdir('logs', 0777, true);
        }
        $logger = new Logger('subway');
        $logger->pushProcessor(new MemoryPeakUsageProcessor());
        $logger->pushHandler(new RotatingFileHandler('logs/subway.log', $level));

        return $logger;
    }

    /**
     * Get options
     * 
     * @return ArrayCollection
     */
    public function getOptions()
    {
        return $this->options;
    }
}