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

$GLOBALS['TL_DCA']['tl_settings']['fields']['sessionTimeout']['save_callback'][] = array('tl_settings_phpbbforum', 'updateSessionTimeoutConfig');
//$GLOBALS['TL_DCA']['tl_settings']['fields']['sessionTimeout']['save_callback'][] = array('tl_settings_phpbbforum', 'updateAutologinConfig');

class tl_settings_phpbbforum extends tl_settings {

    public function updateSessionTimeoutConfig($varvalue){
        $result = System::getContainer()->get('phpbb_bridge.connector')->updateDbConfig('session_length', $varvalue);

        if($result > 0){
            Message::addInfo("Session Expire Timeout updated in Forum");
        }

        return $varvalue;
    }

    /**
     *
     * @todo Implement provider->autologin before saving here
     * @param $varvalue
     * @return mixed
     */
    public function updateAutologinConfig($varvalue){
        //System::getContainer()->get('phpbb_bridge.connector')->updateDbConfig('max_autologin_time', $varvalue);

        return $varvalue;
    }

}