<?php

namespace Ngld\CommonBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NgldCommonExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if(isset($config['filters']) && isset($config['filters']['lessc'])) {
            $lc = $config['filters']['lessc'];
            if(isset($lc['bin']))
                $container->setParameter('ngld.common.lessc.bin', $lc['bin']);

            $container->setParameter('ngld.common.lessc.compress', $lc['compress']);
            $container->setParameter('ngld.common.lessc.ie_compat', $lc['ie_compat']);
            $container->setParameter('ngld.common.lessc.source_map', $lc['source_map']);
        }

        $this->addClassesToCompile(array(
            'Ngld\\CommonBundle\\DependencyInjection\\ContainerRef',
        ));
    }
}
