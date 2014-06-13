<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Command;

use Subway\Config;
use Subway\Factory;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Config aware command
 */
abstract class ConfigAwareCommand extends Command
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Client
     */
    private $redis;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Set config
     * 
     * @param  Config $config
     * @return self
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config
     * 
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set redis
     * 
     * @param  Client $redis
     * @return self
     */
    public function setRedis(Client $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * Get redis
     * 
     * @return Client
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Set factory
     * 
     * @param  Factory $factory
     * @return self
     */
    public function setFactory(Factory $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Get factory
     * 
     * @return Client
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Set logger
     * 
     * @param  LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger
     * 
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
