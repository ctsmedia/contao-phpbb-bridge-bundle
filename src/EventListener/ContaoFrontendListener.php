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
            $loginResult = System::getContainer()->get('phpbb_bridge.connector')->login($username, $password, false, true);
            // Only import user if login to forum succeeded
            if ($loginResult === true) {
                System::log("Importing User: ".$username, __METHOD__ ,TL_ACCESS);
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


}