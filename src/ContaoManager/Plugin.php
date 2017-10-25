<?php
/*
 * This file is part of contao-phpbb-bridge-bundle
 * 
 * Copyright (c) CTS GmbH
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 */

namespace Ctsmedia\Phpbb\BridgeBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Ctsmedia\Phpbb\BridgeBundle\CtsmediaPhpbbBridgeBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\ContaoManager
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(CtsmediaPhpbbBridgeBundle::class)
                        ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }


    /**
     * Returns a collection of routes for the bridge
     *
     * @param LoaderResolverInterface $resolver
     * @param KernelInterface $kernel
     *
     * @return null|RouteCollection
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__ . '/../Resources/config/routing.yml')
            ->load(__DIR__ . '/../Resources/config/routing.yml')
            ;
    }


}