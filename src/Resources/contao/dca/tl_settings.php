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
$GLOBALS['TL_DCA']['tl_settings']['fields']['autologin']['save_callback'][] = array('tl_settings_phpbbforum', 'updateAutologinConfig');

class tl_settings_phpbbforum extends tl_settings {

    public function updateSessionTimeoutConfig($varvalue){
        $result = System::getContainer()->get('phpbb_bridge.connector')->updateDbConfig('session_length', $varvalue);

        if($result > 0){
            Message::addInfo("Session Expire Timeout updated in Forum");
            System::getContainer()->get('phpbb_bridge.connector')->clearForumCache();
        }

        return $varvalue;
    }

    /**
     *
     * Sync the autologin expire value
     * The value must be dividable by a day in seconds = 86400
     *
     * @throws Exception
     * @param $varvalue
     * @return mixed
     */
    public function updateAutologinConfig($varvalue){
        if($varvalue % 86400 != 0) {
            throw new Exception('Value must be dividable by 86400 (seconds of day)');
        } else {
            $result = System::getContainer()->get('phpbb_bridge.connector')->updateDbConfig('max_autologin_time', $varvalue / 86400);
            if($result > 0){
                Message::addInfo("Autologin Expire Timeout updated in Forum to ".($varvalue / 86400)." days");
                System::getContainer()->get('phpbb_bridge.connector')->clearForumCache();
            }
        }

        return $varvalue;
    }

}