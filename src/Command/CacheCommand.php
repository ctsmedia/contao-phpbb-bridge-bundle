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

namespace Ctsmedia\Phpbb\BridgeBundle\Command;

use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\System;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\Command
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class CacheCommand extends ContainerAwareCommand
{
    use FrameworkAwareTrait;

    protected function configure()
    {
        $this
            ->setName('phpbb_bridge:cache')
            ->setDescription('Clears phpbb caches und layout files')
            ->addOption(
                'cache-only',
                'c',
                InputOption::VALUE_NONE,
                'Clean only the cache, not generate the layout?'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getFramework()->initialize();
        $output->writeln('Clearing Forum Cache');
        System::getContainer()->get('phpbb_bridge.connector')->clearForumCache();

        // Generate the layout if not explicitly asked for cache only
        if(!$input->getOption('cache-only')){
            $output->writeln('Generating Layout Files');
            System::getContainer()->get('phpbb_bridge.connector')->generateForumLayoutFiles();
        }

        return 0;

    }


}