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

use Contao\Database;
use Contao\Encryption;
use Contao\FrontendUser;
use Contao\Input;
use Contao\System;
use Contao\User;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\EventListener
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class ContaoFrontendListener
{

    public function onGenerateFrontendUrl(array $arrRow, $strParams, $strUrl)
    {

        if (isset($arrRow['type']) && $arrRow['type'] == 'phpbb_forum' && (!isset($arrRow['skipInternalHook']) && $arrRow['skipInternalHook'] !== true)) {

            return $arrRow['phpbb_alias'] . "/index.php";
        }


        return $strUrl;
    }

    /**
     * Contao ImportUser Hook Implementation
     *
     * Imports a user from phpbb if login credentials match
     *
     * @param $username
     * @param $password
     * @param $scope
     * @return bool
     */
    public function onImportUser($username, $password, $scope)
    {
        if ($scope == 'tl_member') {

            // Before we try to import a user, we must check is username is maybe = username_clean
            // We already now that the user could not be found by username column
            if(($real_username = $this->findByCleanUsername($username)) != ''){
                // So we found the user by it's clean username, then we overwrite the POST Value
                // because contao will recheck it.
                Input::setPost('username', $real_username);
                return true;
            }

            $loginResult = System::getContainer()->get('phpbb_bridge.connector')->login($username, $password, false, true);
            // Only import user if login to forum succeeded
            if ($loginResult === true) {
                System::log("Trying to import User: ".$username, __METHOD__ ,TL_ACCESS);
                // Try to import the user to contao (tl_member / frontend)
                $importResult = System::getContainer()->get('phpbb_bridge.connector')->importUser($username, $password);
                return $importResult; // Should usually be true
            }
        }

        return false;
    }

    /**
     * Check if user authentication succeeds on phpbb site and if so update contao member
     *
     * @param $username
     * @param $password
     * @param User $user
     * @return bool
     */
    public function onCheckCredentials($username, $password, User $user)
    {
        // Only try to login if it's frontend user
        if ($user instanceof FrontendUser) {
            $loginResult = System::getContainer()->get('phpbb_bridge.connector')->login($username, $password, false,  true);
            // Login was successful on phpbb side. Maybe user changed his password. So do we for contao then
            if ($loginResult === true) {
                $user->password = Encryption::hash($password);
                $user->save();
                return true;
            }
        }
        return false;
    }

    /**
     * Login to phpbb if contao login was successful
     *
     * @todo if onImportUser has been run the user is already loggedIn. Maybe we should set a flag in onImportUser or session to skip this
     *
     * @param User $user
     */
    public function onLogin(User $user)
    {
        // Sync login if it's a frontend login attempt, the password is cleary set and
        // if the original request is not from phpbb already
        if ($user instanceof FrontendUser && Input::postUnsafeRaw('password')
            && System::getContainer()->get('request')->attributes->get('isInternalForumRequest', false) === false
        ) {
            $result = System::getContainer()->get('phpbb_bridge.connector')->login($user->username,
                Input::postUnsafeRaw('password'), (bool)Input::post('autologin'));

            if ($result === false) {
                System::log('Could not login user to phpbb after successfull login to contao: ' . $user->username,
                    __METHOD__, TL_ACCESS);
                // @todo Should we then update the password on phpbb side because the user maybe changed the password from contao side
            }
        }

    }

    /**
     * Logout the user the from the forum if logged out from contao
     *
     * @param User $user
     */
    public function onLogout(User $user)
    {
        if ($user instanceof FrontendUser
            && System::getContainer()->get('request')->attributes->get('isInternalForumRequest', false) === false
        ) {
            System::getContainer()->get('phpbb_bridge.connector')->logout();
        }
    }


    public function onReplaceInsertTags($tag) {
        $elements = explode('::', $tag);

        $value = false;
        $phpbbCon = System::getContainer()->get('phpbb_bridge.connector');
        $phpbbUrl = $phpbbCon->getBridgeConfig('url').'/'.$phpbbCon->getForumPath().'/';

        // Non parameter tags
        if($elements[0] == 'phpbb_bridge' ){

            switch($elements[1]) {
                case 'page_profile':
                    $phpbbUserId = $phpbbCon->getCurrentUser() !== null ? $phpbbCon->getCurrentUser()->user_id : 1;
                    $value = $phpbbUrl . 'memberlist.php?mode=viewprofile&u='.$phpbbUserId;
                    break;
                case 'page_login':
                    $value = $phpbbUrl .  'ucp.php?mode=login';
                    break;
                case 'page_logout':
                    $phpbbSid = $phpbbCon->getCurrentUser() !== null ? $phpbbCon->getCurrentUser()->session_id : '';
                    $value = $phpbbUrl . 'ucp.php?mode=logout&sid='.$phpbbSid;
                    break;
                case 'page_resetpassword':
                    $value = $phpbbUrl .'ucp.php?mode=sendpassword';
                    break;
                case 'page_ucp':
                    $value = $phpbbUrl .'ucp.php';
                    break;
                case 'page_register':
                    $value = $phpbbUrl .'ucp.php?mode=register';
                    break;
            }
        }

        // dynamic parameter tags
        if(strpos($elements[0], 'phpbb_bridge_') !== false ){
            switch($elements[0]){
                case 'phpbb_bridge_user_profile':
                    $user_id = $elements[1];
                    // if we got a username, try to find the appropriate user id
                    if(!is_numeric($user_id)){
                        $user = $phpbbCon->getUser($user_id);
                        if($user_id !== null) {
                            $user_id = $user->user_id;
                        }
                    }
                    $value = $phpbbUrl . 'memberlist.php?mode=viewprofile&u='.((int)$user_id);
            }
        }

        return $value;
    }



    /**
     * Find a user in the database
     *
     * @param mixed  $username  The clean username
     *
     * @return string The correspondending username or an empty string
     */
    protected function findByCleanUsername($username)
    {
        $db = Database::getInstance();
        $objResult = $db->prepare("SELECT username FROM tl_member WHERE username_clean=?")
            ->limit(1)
            ->execute($username);

        if ($objResult->numRows > 0)
        {
            return $objResult->row()['username'];
        }

        return '';
    }


}