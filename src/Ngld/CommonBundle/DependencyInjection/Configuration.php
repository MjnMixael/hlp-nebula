<?php

namespace Ngld\CommonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ngld_common');

        $rootNode->children()
            ->arrayNode('filters')
                ->children()
                    ->arrayNode('lessc')
                        ->children()
                            ->scalarNode('bin')->end()
                            ->booleanNode('compress')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('ie_compat')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('source_map')
                                ->defaultTrue()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

}
