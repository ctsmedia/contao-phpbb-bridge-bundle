<?php
/*
 * This file is part of contao-phpbb-bridge-bundle
 * 
 * Copyright (c) 2015-2016 Daniel Schwiperich
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 */

namespace ctsmedia\contaophpbbbridge\event;

use ctsmedia\contaophpbbbridge\contao\Connector;
use phpbb\event\data;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Listener for the main phpbb Events to trigger Contao related actions
 *
 * @package ctsmedia\contaophpbbbridge\event
 * @author Daniel Schwiperich <https://github.com/DanielSchwiperich>
 */
class MainListener implements EventSubscriberInterface
{


    /**
     * MainListener constructor.
     * @param Connector $contaoConnector
     * @param array $config
     */
    public function __construct(Connector $contaoConnector, array $config = [])
    {
        $this->contaoConnector = $contaoConnector;
        $this->config = $config;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'core.ucp_profile_reg_details_sql_ary'	=> 'updateContaoProfile',
            'core.ucp_activate_after'	=> 'resetPassword',
        );
    }

    /**
     * Update contao member if changes are made by the user in phpbb
     *
     * @param data $event
     */
    public function updateContaoProfile(data $event) {

        $data = $event->get_data();

        $hasPasswordChanged = (bool)$data['sql_ary']['user_passchg'];
        $user = $this->contaoConnector->getContaoUser($data['data']['username']);
        
        if($user !== false && isset($user['id'])) {


            $updateUser = array('email' => $data['sql_ary']['user_email'], 'tstamp' => time());
            if($hasPasswordChanged) {
                // This should normaly work for contao. @see Encryption::hash() method
                // If not, it doesn't matter because Contao will ask phpbb if credentials are ok
                $updateUser['password'] = password_hash($data['data']['new_password'], PASSWORD_BCRYPT, ['cost'=>PASSWORD_BCRYPT_DEFAULT_COST]);
            }

            $sql = 'UPDATE tl_member
                    SET ' . $this->contaoConnector->getContaoDbConnection()->sql_build_array('UPDATE', $updateUser) . '
                    WHERE id = ' . $user['id'];

            $this->contaoConnector->getContaoDbConnection()->sql_query($sql);
        }
        
    }

    /**
     * Update the contao password if a new one was set via phpbb password forgotten function
     *
     * @param data $event
     */
    public function resetPassword(data $event) {
        $data  = $event->get_data();
        $phpbbuser = $data['user_row'];


        // new password was activated, time to update contao
        if($data['message'] == 'PASSWORD_ACTIVATED'){
            $user = $this->contaoConnector->getContaoUser($phpbbuser['username']);

            // We set a new password to the contao user, so the old one does not work parallel to the new one
            // We do not have the plain new password here, so we just set a hash based on the activation key
            // on next login via contao side the credential check will fail and contao will ask phpbb if the check works there
            // and if so update the contao password accordingly
            $updateUser = array('password' => md5($phpbbuser['user_actkey']), 'tstamp' => time());

            if($user !== false && isset($user['id'])) {
                $sql = 'UPDATE tl_member
                    SET ' . $this->contaoConnector->getContaoDbConnection()->sql_build_array('UPDATE', $updateUser) . '
                    WHERE id = ' . $user['id'];

                $this->contaoConnector->getContaoDbConnection()->sql_query($sql);
            }
        }

    }



}