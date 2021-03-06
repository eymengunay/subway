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
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/**
 * Config class
 */
class Config implements ConfigurationInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $configs   = array();
        $processor = new Processor();

        if (file_exists('subway.yml.dist')) {
            $configs[] = Yaml::parse('subway.yml.dist');
        }

        if (file_exists('subway.yml')) {
            $configs[] = Yaml::parse('subway.yml');
        }

        $this->config = $processor->processConfiguration($this, $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('subway');

        $rootNode
            ->children()
                ->scalarNode('redis_host')
                    ->defaultValue('localhost:6379')
                    ->info('Redis host')
                ->end()
                ->scalarNode('redis_prefix')
                    ->defaultNull()
                    ->info('Redis prefix')
                ->end()
                ->scalarNode('bridge')
                    ->defaultValue('composer')
                    ->info('Bridge type')
                ->end()
                ->arrayNode('bridge_options')
                    ->prototype('scalar')->end()
                    ->info('Bridge options')
                ->end()
                ->integerNode('interval')
                    ->defaultValue(5)
                    ->info('New job check interval across queues (in seconds)')
                ->end()
                ->integerNode('concurrent')
                    ->defaultValue(5)
                    ->info('Max concurrent job count (in seconds)')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Get config key
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->config) === false) {
            throw new SubwayException("Given key $key doesn't exists");
        }

        return $this->config[$key];
    }

    /**
     * Set config key
     *
     * @param  string $key
     * @param  mixed  $val 
     * @return self
     */
    public function set($key, $val)
    {
        $this->config[$key] = $val;

        return $this;
    }

    /**
     * All
     * 
     * @return array
     */
    public function all()
    {
        return $this->config;
    }
}