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

    protected $debug = false;
    protected $logger = null;

    public function __construct(config $config, ContainerInterface $container, dispatcher $dispatcher, user $user, Connector $contaoConnector, $root_path, $php_ext)
    {
        $this->config = $config;
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->user = $user;
        $this->contaoConnector = $contaoConnector;
        $this->rootPath = $root_path;
        $this->phpExt = $php_ext;

        if($this->debug === true) {
            $this->logger = new \Monolog\Logger('bridge');
            $this->logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__.'/../bridge.log'));
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