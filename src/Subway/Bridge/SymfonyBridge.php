<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Bridge;

use Subway\Bridge;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Symfony bridge
 */
class SymfonyBridge extends Bridge
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        require_once $this->getOptions()->get('autoload');
        require_once $this->getOptions()->get('kernel');

        $kernel = new \AppKernel($this->getOptions()->get('env'), $this->getOptions()->get('debug'));
        $kernel->boot();

        $this->container = $kernel->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'autoload' => 'app/autoload.php',
            'kernel'   => 'app/AppKernel.php',
            'env'      => 'dev',
            'debug'    => true,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get event dispatcher
     * 
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }
}