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

namespace Ctsmedia\Phpbb\BridgeBundle\EventListener;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\EventListener
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class ContaoBackendListener
{

    public function onGetPageStatusIcon($objPage, $image)
    {

        if ($objPage->type == 'phpbb_forum') {
            if ($image == "phpbb_forum.gif") {
                $image = '/bundles/ctsmediaphpbbbridge/phpbb_forum-icon.png';
            }
            if ($image == "phpbb_forum_1.gif") {
                $image = '/bundles/ctsmediaphpbbbridge/phpbb_forum-icon_1.png';
            }

        }

        return $image;

    }

}