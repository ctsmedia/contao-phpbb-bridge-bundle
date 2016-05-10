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

namespace ctsmedia\contaophpbbbridge\controller;

use ctsmedia\contaophpbbbridge\contao\Connector;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use phpbb\config\config;
use phpbb\event\dispatcher;
use phpbb\user;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;


/**
 *
 * @todo security: make this only accessible for contao requests.
 *
 * @package ctsmedia\contaophpbbbridge\controller
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 * @Route("/contao_connect")
 */
class Connect
{

    protected $config;
    protected $dispatcher;
    protected $user;
    protected $contaoConnector;
    protected $rootPath;
    protected $phpExt;
    protected $db_auth;

    protected $debug = false;
    protected $logger = null;

    public function __construct(config $config, ContainerInterface $container, dispatcher $dispatcher, user $user, Connector $contaoConnector, $root_path, $php_ext, \phpbb\auth\provider\db $db_auth)
    {
        $this->config = $config;
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->user = $user;
        $this->contaoConnector = $contaoConnector;
        $this->rootPath = $root_path;
        $this->phpExt = $php_ext;
        $this->db_auth = $db_auth;

        $this->logger = new Logger('bridge_controller');
        $this->logger->pushHandler(new StreamHandler(__DIR__.'/../bridge_error.log'), Logger::ERROR);
        if($this->debug === true) {
            $this->logger->pushHandler(new StreamHandler(__DIR__.'/../bridge.log'), Logger::DEBUG);
        }

    }

    /**
     * Tests if the current user is logged in
     * @return Response
     */
    public function isLoggedIn($refreshSession){

        $response = new JsonResponse();
        $loggedInStatus = ($this->user->data['user_id'] != ANONYMOUS) ? true : false;

        $response->setData(array(
            'logged_in' => $loggedInStatus,
            'data' => $this->user->data
        ));

        // Debug incoming requests
        if($this->debug){
            $cookies = $this->container->get('request')->header('cookie');
            $cookies = explode(";", $cookies);
            foreach ($cookies as $index => $cookie){
                if(strpos($cookie, 'phpbb') === false) unset($cookies[$index]);
            }
            $cookies = implode("||", $cookies);
            $url = $this->container->get('request')->server('REQUEST_URI');
            $this->logger->debug("----------------------------------------------");
            $this->logger->debug("REQ: ".$url . " || refreshSession: ".((bool)$refreshSession));
            $this->logger->debug("Cookies: ".$cookies);
            $this->logger->debug("RES User: ".$this->user->data['user_id']);
        }

        if($refreshSession != false && $loggedInStatus === true){
            $session_data = array('session_time' => time());
            $this->user->update_session($session_data, $this->user->session_id);
        }


        return $response;
    }

    public function isValidLogin($username, $password) {
        $status = false;
                 
        // We only allow internal requests from Contao
        if ($this->container->get('request')->header('X-Requested-With') == 'ContaoPhpbbBridge') {

            //@todo continue here
            // @todo decrease and increase ausgleich
            
            // Get user and decrease login attempt. This one here should not count
            $username_clean = utf8_clean_string($username);
            $sql = 'SELECT *
			FROM ' . USERS_TABLE . "
			WHERE username_clean = '" . $this->container->get('dbal.conn')->sql_escape($username_clean) . "'";
            $result = $this->container->get('dbal.conn')->sql_query($sql);
            $row = $this->container->get('dbal.conn')->sql_fetchrow($result);
            $this->container->get('dbal.conn')->sql_freeresult($result);

            // test against a db login 
            $result = $this->db_auth->login($username, $password);
            if($result['status'] == LOGIN_SUCCESS){
                $status = true;
            }

            // Password incorrect - increase login attempts
            $sql = 'UPDATE ' . USERS_TABLE . '
			SET user_login_attempts = user_login_attempts + 1
			WHERE user_id = ' . (int) $row['user_id'] . '
				AND user_login_attempts < ' . LOGIN_ATTEMPTS_MAX;
            //$this->db->sql_query($sql);


        };
        
        $response = new JsonResponse();
        $response->setData(array(
            'status' => $status,
        ));
        
        return $response;
        
    }

    /**
     * Purges the phpbb cache
     */
    public function purgeCache(){
        $response =  new JsonResponse();
        $status = true;
        $data = [];

        try {
            $this->container->get('cache')->purge();
        } catch(Exception $e){
            $status = false;
            $data['error_message'] = $e->getMessage();
        }

        $response->setData(array(
            'status' => $status,
            'data' => $data
        ));

        return $response;
    }

    /**
     *
     * @Route("/test")
     */
    public function test()
    {
        $response =  new JsonResponse();
        $response->setData(array(
            'status' => $this->contaoConnector->isInstalled(),
            'data' => []
        ));

        return $response;
    }

}