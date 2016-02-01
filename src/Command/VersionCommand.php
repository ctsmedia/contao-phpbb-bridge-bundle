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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 *
 * @package Command
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class VersionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('phpbb_bridge:version')
            ->setDescription('Outputs the Bridge Version')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packages = $this->getContainer()->getParameter('kernel.packages');

        if (!isset($packages['ctsmedia/contao-phpbb-bridge-bundle'])) {
            return 1;
        }

        $output->writeln($packages['ctsmedia/contao-phpbb-bridge-bundle']);

        return 0;
    }

}