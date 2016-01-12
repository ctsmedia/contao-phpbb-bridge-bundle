<?php
/*
 * This file is part of contao-phpbbBridge
 * 
 * Copyright (c) CTS GmbH
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 */

namespace Ctsmedia\Phpbb\BridgeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\DependencyInjection
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class CtsmediaPhpbbBridgeExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        $container->setParameter('phpbb_bridge.dir', $mergedConfig['dir']);
        $container->setParameter('phpbb_bridge.db.table_prefix', $mergedConfig['db']['table_prefix']);
    }


}