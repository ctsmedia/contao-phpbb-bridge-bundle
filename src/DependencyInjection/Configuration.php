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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\DependencyInjection
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {

        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('phpbb_bridge');

        $rootNode
            ->children()
                ->scalarNode('phpbb_dir')
                    ->defaultValue('phpBB3')
                ->end()
            ->end();

        return $treeBuilder;
    }
}