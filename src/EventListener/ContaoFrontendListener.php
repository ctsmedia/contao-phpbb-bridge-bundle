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

            return $arrRow['phpbb_alias'] . "/index.php";
        }


        return $strUrl;
    }

    public function onImportUser($username, $password, $scope) {
        dump("Hook Import User called");
        if ($scope == 'tl_member')
        {
            System::getContainer()->get('phpbb_bridge.connector')->login($username, $password);
        }

        return false;
    }



}