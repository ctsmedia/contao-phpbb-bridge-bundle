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

use Contao\System;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\EventListener
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class ContaoFrontendListener
{

    public function onGenerateFrontendUrl(array $arrRow, $strParams, $strUrl){

        if(isset($arrRow['type']) && $arrRow['type'] == 'phpbb_forum' && (!isset($arrRow['skipInternalHook']) && $arrRow['skipInternalHook'] !== true )){

            //@TODO replace with dca value
            return System::getContainer()->getParameter('phpbb_bridge.phpbb_dir') . "/index.php";
        }


        return $strUrl;
    }

}