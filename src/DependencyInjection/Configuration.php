<?php

namespace Pim\Bundle\TextmasterBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
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

        $rootNode = $treeBuilder->root('textmaster');

        $rootNode->children()
            ->scalarNode('api_key')->end()
            ->scalarNode('api_secret')->end()
            ->scalarNode('attributes')->end()
            ->booleanNode('autolaunch')->end()
            ->end();

        $rootNode->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'api_key' => ['value' => null],
                'api_secret' => ['value' => null],
                'attributes' => ['value' => null],
                'autolaunch' => ['value' => false],
            ]
        );

        return $treeBuilder;
    }
}
