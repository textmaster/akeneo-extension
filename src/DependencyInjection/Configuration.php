<?php

namespace Pim\Bundle\TextmasterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $root = $treeBuilder->root('textmaster');

        $children = $root->children();

        $children->arrayNode('settings')
            ->children()
                ->arrayNode('api_key')
                    ->children()
                        ->scalarNode('value')->end()
                        ->scalarNode('scope')->end()
                    ->end()
                ->end()
                ->arrayNode('api_secret')
                    ->children()
                        ->scalarNode('value')->end()
                        ->scalarNode('scope')->end()
                    ->end()
                ->end()
                ->arrayNode('attributes')
                    ->children()
                        ->scalarNode('value')->end()
                        ->scalarNode('scope')->end()
                    ->end()
                ->end()
                ->arrayNode('autolaunch')
                    ->children()
                        ->booleanNode('value')->end()
                        ->scalarNode('scope')->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        $children->end();

        $root->end();

        return $treeBuilder;
    }
}
