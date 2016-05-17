<?php
/*
 * This file is part of contao-phpbbBridge
 * 
 * Copyright (c) 2015-2016 Daniel Schwiperich
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 */

namespace Ctsmedia\Phpbb\BridgeBundle\EventListener;


/**
 * Listener for some Backend Events 
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\EventListener
 * @author Daniel Schwiperich <https://github.com/DanielSchwiperich>
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