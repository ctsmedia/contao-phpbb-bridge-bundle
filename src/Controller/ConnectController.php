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

namespace Ctsmedia\Phpbb\BridgeBundle\Controller;

use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Environment;
use Contao\FrontendIndex;
use Contao\FrontendUser;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Ctsmedia\Phpbb\BridgeBundle\PageType\Forum;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Controller for Bridge requests from phpbb side
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\Controller
 * @author Daniel Schwiperich <https://github.com/DanielSchwiperich>
 *
 * @Route("/phpbb_bridge", defaults={"_scope" = "frontend"})
 */
class ConnectController extends Controller
{
    /**
     * @var FrontendIndex null
     */
    protected $frontendIndex = null;

    protected $debug = false;


    /**
     * Call this function to validate the incoming request
     * @todo move the validation in symfony security firewall authentication provider or request_matcher ?
     *
     * __IMPORTANT INFO__ Contao hooks into the kernel request and tests for user authentication, which can result into a
     * redirect / reload if autologin was successful. The request headers should be kept though
     *
     */
    protected function validateRequest()
    {
        // Initialize Contao
        $this->container->get('contao.framework')->initialize(); // we need to do this for autoloading contao classes
        $req = $this->container->get('request_stack')->getCurrentRequest();
        /* @var $req Request */

        // Only requests from the bridge itself are allowed. Check if the specific header is set
        if ($req->headers->get('x-requested-with') != 'ContaoPhpbbBridge') {
            System::log('Not allowed to access phpbb bridge. Seems not coming fron the bridge. Path: '.$req->getPathInfo(), __METHOD__, TL_ERROR);
            if(!$this->debug) throw new AccessDeniedException('Not allowed to access phpbb bridge');
        }
        // The bridge also always sets a internal proxy header
        if (!$req->headers->get('x-forwarded-for')) {
            System::log('Not allowed to access phpbb bridge without proxy header', __METHOD__, TL_ERROR);
            if(!$this->debug) throw new AccessDeniedException('Not allowed to access phpbb bridge without proxy header');
        }

        // Make sure we have an internat request
        // we cannot use $req->server->get('REMOTE_ADDR') here, because symfone alters it
        if($_SERVER['REMOTE_ADDR'] != Environment::get('server') ){
            System::log('IPs did not match. clientIP: '.
                $req->getClientIp().'| EnvClientIp '.Environment::get('ip').'| EnvServerIp '.Environment::get('server'),
            __METHOD__, TL_ERROR);
            if(!$this->debug) throw new AccessDeniedException('Not allowed to access phpbb bridge without proxy header');
        }

        if($this->debug) System::log('Origin Request: '.$req->headers->get('x-requested-origin', '/NotSet'), __METHOD__ , TL_ACCESS);

        $req->attributes->set('isInternalForumRequest', true);
        $this->frontendIndex = new FrontendIndex();

    }

    /**
     * @Route("/autologin")
     * @return Response
     */
    public function autologinAction(){
        $this->validateRequest();

        $isLoggedIn = false;
        $userId     = 0;
        
        if(FE_USER_LOGGED_IN === true) {
            $username = $this->container->get('security.token_storage')->getToken()->getUsername();
            $phpBBuser = $this->container->get('phpbb_bridge.connector')->getUser($username);

            if($phpBBuser !== null) {
                $isLoggedIn = true;
                $userId     = $phpBBuser->user_id;
            } else {
                System::log('Request from phpbb to autologin a user which was found in Contao but not in phpbb: '.$username, __METHOD__, TL_ERROR);
            }
        }

        $response = new JsonResponse();
        $response->setData(array(
            'is_logged_in' => $isLoggedIn,
            'user_id'      => (int)$userId
        ));

        return $response;
    }


    /**
     * @Route("/ping")
     * @return Response
     */
    public function pingAction(){
        $this->validateRequest();

        $response = new JsonResponse();
        $response->setData(array(
            'message' => "pong"
        ));

        return $response;
    }

    /**
     *
     * @Route("/test")
     */
    public function testAction()
    {
        $this->validateRequest();

        return new Response();

//        $content = dump(Config::get('disableIpCheck'));
//
//        $response = new Response();
//        $response->setContent($content);
//
//        return $response;
    }

    /**
     * Login a user to contao via incoming phpbb POST login request
     *
     * @Route("/login")
     */
    public function loginAction()
    {
        $this->validateRequest();

        $user = FrontendUser::getInstance();
        $result = $user->login();
        $statusCode = 'SUCCESS';

        if($result === false) {
            $statusCode = 'FAILURE';
        }
        
        // A user was locked so we also need to lock on phpbb side
        if($result === false && $user->locked > 0 && $user->locked + Config::get('lockPeriod') > time()) {
            $statusCode = 'LOCKED';
        }

        $response = new JsonResponse();
        $response->setData(array(
            'status' => $result,
            'code'  => $statusCode
        ));

        return $response;
    }

    /**
     * Logout a user from contao via incoming phpbb logout request
     *
     * @Route("/logout")
     */
    public function logoutAction()
    {
        $this->validateRequest();
        $user = FrontendUser::getInstance();
        $result = $user->logout();

        $response = new JsonResponse();
        $response->setData(array(
            'logout_status' => $result
        ));

        return $response;
    }

    /**
     *
     * @Route("/layout")
     */
    public function layoutAction()
    {
        $this->validateRequest();

        $objPage = PageModel::findOneByType('phpbb_forum');
        // Set the correct current page for navigation
        Environment::set('relativeRequest',
            $this->container->get('phpbb_bridge.connector')->getBridgeConfig('forum_pageId')
            .$GLOBALS['TL_CONFIG']['urlSuffix']
        );
        $response = $this->frontendIndex->run();
        if ($objPage instanceof PageModel) {
            $page = new Forum();
            Input::setGet('format', 'json');
            $response = $page->getResponse($objPage);
        }
        return $response;
    }

}