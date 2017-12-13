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

namespace ctsmedia\contaophpbbbridge\contao;

use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use phpbb\auth\provider\db;


/**
 * Contao Auth provider
 *
 * Syncs phpbb authentication to contao. While contao is the provider / responsible
 * Extends the default phpbb authenticator for local checks
 *
 *
 * @package ctsmedia\contaophpbbbridge\contao
 * @author Daniel Schwiperich <https://github.com/DanielSchwiperich>
 */
class AuthProvider extends db
{

    protected $contaoConnector;

    protected $logoutInProgress = false;

    protected $debug = false;

    /**
     * AuthProvider constructor.
     */
    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\passwords\manager $passwords_manager, \phpbb\request\request $request, \phpbb\user $user, \Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container, $phpbb_root_path, $php_ext, Connector $contaoConnector)
    {
        parent::__construct($db, $config, $passwords_manager, $request, $user, $phpbb_container, $phpbb_root_path, $php_ext);
        $this->contaoConnector = $contaoConnector;

        $this->logger = new Logger('bridge_auth');
        $this->logger->pushHandler(
            new FingersCrossedHandler(new StreamHandler(__DIR__.'/../bridge_error.log'), Logger::ERROR)
        );
        $this->logger->pushHandler(new StreamHandler(__DIR__.'/../bridge.log', Logger::DEBUG));

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
        $hasContaoAutologinCookie = $this->request->variable('FE_AUTO_LOGIN', false, true, \phpbb\request\request_interface::COOKIE);

        if ($this->debug) {
            $this->logger->debug(__METHOD__, ['hasAuthCookie' => $hasContaoAuthCookie,'hasAutologinCookie' => $hasContaoAutologinCookie ,  $user['user_id']]);
        }

        // If we are at a anonymous session but find a active contao user auth cookie the user most likely has logged in
        // we should try to log the user in
        if($user['user_id'] == ANONYMOUS && $hasContaoAuthCookie) {
            return false;
        }

        // A logout must has happened somewhere.
        if($user['user_id'] > ANONYMOUS && !$hasContaoAuthCookie) {
            return false;
        }

        // One last check. If we have a autologin cookie and the phpuserid is anonymous but the phpbb session is still active
        // (otherwise we would not be in here) then we mark the session expired. This should only be the case if s1 logs
        // in with autologin enabled and deletes the FE_AUTH cookie on purpose. In other cases the the phpbb session and
        // contao auth end at the same time.
        if($user['user_id'] == ANONYMOUS && $hasContaoAutologinCookie) {
            return false;
        }
    }

    /**
     * Tries to autologin a user
     *
     * @return array
     */
    public function autologin() {

        // Don't put anything in here like the ANONYMOUS user id. it can partially break phpbb session generation.
        // Always needs a complete row
        $user_data = [];

        if ($this->debug) {
            $this->logger->debug(__METHOD__, ['logoutInProgress' => $this->logoutInProgress]);
        }

        // In admin area we completely skip contao
        if(defined('ADMIN_START')) {
            if ($this->debug) {
                $this->logger->debug('Admin Area detected. Skipping Contao autologin');
            }
            return;
        }

        // phpbb initializes a new session after logout without reload
        // so the autologin cookies are still in the current request. So just stop here
        if($this->logoutInProgress === true) {
            return $user_data;
        }

        //Try to autologin via contao
        try {
            $userId = $this->contaoConnector->autologin();

            // If found look for the user in phpbb db
            if($userId > ANONYMOUS){
                $sql = 'SELECT u.*
				FROM ' . USERS_TABLE .' u 
				WHERE u.user_id = ' . (int) $userId . '
                AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')';
                $result = $this->db->sql_query($sql);
                $user_data = $this->db->sql_fetchrow($result);
            }

            if ($this->debug) {
                $this->logger->debug('Autologin successfull for ', [$user_data['user_id'], $user_data['username']]);
            }

        // The exception is thrown if no suitable Contao Cookie is found
        // so the request to contao can be saved
        } catch(\InvalidArgumentException $e) {}

        // We want to avoid that users can be auto logged in to phpbb via cookies
        // so we clean the table, because phpbb does not check if it's allowed to autologin the user
        if(empty($user_data)) {

            $sql = 'DELETE FROM ' . SESSIONS_KEYS_TABLE;
            $this->db->sql_query($sql);
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

        if ($this->debug) {
            $this->logger->debug(__METHOD__, ['user' => $username]);
        }

        $result = parent::login($username, $password);
        $contaoLogin = null;

        // In admin area we completely skip contao
        if(defined('ADMIN_START')) {
            if ($this->debug) {
                $this->logger->debug('Admin Login detected. Skipping Contao login sync', ['user' => $username]);
            }
            return $result;
        }

        // if the user is not found in the phpbb user base look at contao and import
        if($result['status'] == LOGIN_ERROR_USERNAME){
            $contaoUser = $this->contaoConnector->getContaoUser($username);

            // found a contao user, try to import
            if (false !== $contaoUser) {
                try{
                    if($this->debug) {
                        $this->logger->debug("User {$username} not found in phpbb on login attempt. Trying to import");
                    }
                    $this->contaoConnector->importUser($username);
                    $result = parent::login($username, $password);
                } catch (\InvalidArgumentException $e) {}
            }
        }

        // if the password check is wrong it maybe due to a imported user from contao
        if($result['status'] == LOGIN_ERROR_PASSWORD){
            // test if phpbb user password is prefixed with import password prefix
            if(false !== strpos($result['user_row']['user_password'], Connector::IMPORT_USER_PASSWORD_PREFIX)) {
                $contaoLogin = $this->contaoConnector->login($username, $password, $this->request->is_set_post('autologin'));

                if ($this->debug) {
                    $this->logger->debug('Login on imported user with import password. Trying to login via contao and set password' , ['contao_login_status' => $contaoLogin['status']]);
                }

                // Set password to imported phpbbuser
                if($contaoLogin['status'] === true) {
                    $sql = "UPDATE " . USERS_TABLE .
                        " SET user_password = '{$this->db->sql_escape($this->passwords_manager->hash($password))}'" .
                        " WHERE user_id = " . (int)$result['user_row']['user_id'];
                    $this->db->sql_query($sql);
                    $result = parent::login($username, $password);
                }
            }
        }

        // We only need to trigger contao login if the phpbb login was successful
        if($result['status'] == LOGIN_SUCCESS){
            $contaoLogin = ($contaoLogin !== null) ? $contaoLogin : $this->contaoConnector->login($username, $password, $this->request->is_set_post('autologin'));

            // Account was locked on contao side
            if($contaoLogin['status'] === false && $contaoLogin['code'] == 'LOCKED') {
                $result =  array(
                    'status'	=> LOGIN_ERROR_EXTERNAL_AUTH,
                    'error_msg'	=> 'CONTAO_LOGIN_LOCKED',
                    'user_row'	=> array('user_id' => ANONYMOUS),
                );
            // Contao did not want to login the user. See Contao log for more info
            } elseif($contaoLogin['status'] === false) {
                $result =  array(
                    'status'	=> LOGIN_ERROR_EXTERNAL_AUTH,
                    'error_msg'	=> 'CONTAO_LOGIN_FAILED',
                    'user_row'	=> array('user_id' => ANONYMOUS),
                );
            }
        }

        if ($this->debug) {
            $this->logger->debug('Loginstatus' , [$result['status']]);
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

        if ($this->debug) {
            $this->logger->debug(__METHOD__, ['user_id' => $data['user_id'], 'newSession' => $new_session]);
        }

        $this->contaoConnector->logout();
        $this->logoutInProgress = true;
        parent::logout($data, $new_session);
    }
}