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

namespace ctsmedia\contaophpbbbridge\contao;

use phpbb\auth\provider\db;


/**
 * Contao Auth provider
 *
 * Only extends the default db authentication plugin so we can hook into the login, logout, ... processes
 * because there exists for some of those no default events.
 * Feels also cleaner
 *
 * The provider just uses default func and additionally sends the auth data to contao via the connector
 *
 * @see
 *
 * @package ctsmedia\contaophpbbbridge\contao
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class AuthProvider extends db
{

    protected $contaoConnector;

    /**
     * AuthProvider constructor.
     */
    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\passwords\manager $passwords_manager, \phpbb\request\request $request, \phpbb\user $user, \Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container, $phpbb_root_path, $php_ext, Connector $contaoConnector)
    {
        parent::__construct($db, $config, $passwords_manager, $request, $user, $phpbb_container, $phpbb_root_path, $php_ext);
        $this->contaoConnector = $contaoConnector;

    }


    /**
     * The session validation function checks whether the user is still logged
     * into phpBB.
     *
     * @param 	array 	$user
     * @return 	boolean	true if the given user is authenticated, false if the
     * 					session should be closed, or null if not implemented.  *
     * @param array $user
     * @return bool
     */
    public function validate_session($user)
    {
        $hasContaoAuthCookie = $this->request->variable('FE_USER_AUTH', false, true, \phpbb\request\request_interface::COOKIE);

        // If we are at a anonymous session but find a active contao user auth cookie the user most likely has logged in
        // we should try to log the user in
        if($user['user_id'] == ANONYMOUS && $hasContaoAuthCookie) {
            return false;
        }

        // A logout must has happened somewhere.
        if($user['user_id'] > ANONYMOUS && !$hasContaoAuthCookie) {
            return false;
        }
    }

    /**
     * Tries to autologin a user
     *
     * @return array
     */
    public function autologin() {

        $user_data = [];

        //Try to autologin via contao
        try {
            $userId = $this->contaoConnector->autologin();
        // The exception is thrown if no suitable Contao Cookie is found
        // so the request can be saved
        } catch(\InvalidArgumentException $e) {
            return $user_data;
        }

        // If found look for the user in phpbb db
        if($userId > ANONYMOUS){
            $sql = 'SELECT u.*
				FROM ' . USERS_TABLE .' u 
				WHERE u.user_id = ' . (int) $userId . '
                AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')';
            $result = $this->db->sql_query($sql);
            $user_data = $this->db->sql_fetchrow($result);
        }

        return $user_data;
    }

    /**
     * Login a user to phpbb and on success also to contao
     *
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login($username, $password)
    {

        $result = parent::login($username, $password);
        // We only need to trigger contao login if the phpbb login was successful
        if($result['status'] == LOGIN_SUCCESS){
            $contaoLogin = $this->contaoConnector->login($username, $password, $this->request->is_set_post('autologin'));
            if($contaoLogin === false) {
                $result =  array(
                    'status'	=> LOGIN_ERROR_EXTERNAL_AUTH,
                    'error_msg'	=> 'CONTAO_LOGIN_FAILED',
                    'user_row'	=> array('user_id' => ANONYMOUS),
                );
            }
        }

        return $result;
    }

    /**
     * Logouts a user from phpbb and contao
     *
     * @param array $data
     * @param bool $new_session
     */
    public function logout($data, $new_session)
    {
        $this->contaoConnector->logout();
        parent::logout($data, $new_session);
    }
}