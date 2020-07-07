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

$GLOBALS['TL_DCA']['tl_settings']['fields']['sessionTimeout']['save_callback'][] = array('tl_settings_phpbbforum', 'updateSessionTimeoutConfig');
$GLOBALS['TL_DCA']['tl_settings']['fields']['autologin']['save_callback'][] = array('tl_settings_phpbbforum', 'updateAutologinConfig');
$GLOBALS['TL_DCA']['tl_settings']['fields']['lockPeriod']['save_callback'][] = array('tl_settings_phpbbforum', 'updateLoginLock');
$GLOBALS['TL_DCA']['tl_settings']['config']['onsubmit_callback'][] = array('tl_settings_phpbbforum', 'onSubmitCallback');


class tl_settings_phpbbforum {

    protected $clearPhpbbCache = false;

    /**
     * Clears the phpbb cache if relevant config values of tl_settings have changed
     *
     * @param DC_File $tl_settings
     */
    public function onSubmitCallback(DC_File $tl_settings){
        if($this->clearPhpbbCache === true){
            Message::addInfo("phpBB Bridge: Config Values have been changed. Clearing Forum Cache");
            System::getContainer()->get('phpbb_bridge.connector')->setMandatoryDbConfigValues();
            System::getContainer()->get('phpbb_bridge.connector')->clearForumCache();
        }
        System::getContainer()->get('phpbb_bridge.connector')->testCookieDomain();
    }

    /**
     * Syncs Login Lock settings
     *
     * @param $varvalue
     * @return mixed
     */
    public function updateLoginLock($varvalue){
        $result[] = System::getContainer()->get('phpbb_bridge.connector')->updateDbConfig('ip_login_limit_time', $varvalue);
        $result[] = System::getContainer()->get('phpbb_bridge.connector')->updateDbConfig('max_login_attempts', 3);

        if($result[0] > 0){
            Message::addInfo("phpBB Bridge: IP Login Lock Time has been updated to ".$varvalue);
        }
        if($result[1] > 0){
            Message::addInfo("phpBB Bridge: Max Login attempts have been set to 3");
        }

        if($result[1] > 0 || $result[0] > 0){
            $this->clearPhpbbCache = true;
        }

        return $varvalue;
    }

    /**
     * Syncs session expiration
     *
     * @param $varvalue
     * @return mixed
     * @throws Exception
     */
    public function updateSessionTimeoutConfig($varvalue){
        // phpbb always add 60sec to the configured value. To stay in sync the min. allowed value has to be 60
        if($varvalue < 60) {
            throw new Exception('Value must be higher than 60');
        }
        $result = System::getContainer()->get('phpbb_bridge.connector')->updateDbConfig('session_length', ($varvalue - 60));

        if($result > 0){
            Message::addInfo("phpBB Bridge: Session Expire Timeout updated in Forum");
            $this->clearPhpbbCache = true;
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
                Message::addInfo("phpBB Bridge: Autologin Expire Timeout updated in Forum to ".($varvalue / 86400)." days");
                $this->clearPhpbbCache = true;
            }
        }

        return $varvalue;
    }



}
