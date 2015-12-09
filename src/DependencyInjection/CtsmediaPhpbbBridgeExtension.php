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

use Symfony\Component\DependencyInjection\ContainerBuilder;
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

        $container->setParameter('phpbb_bridge.phpbb_dir', $mergedConfig['phpbb_dir']);

    }


}