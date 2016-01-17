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

namespace Ctsmedia\Phpbb\BridgeBundle\Controller;

use Contao\CoreBundle\ContaoCoreBundle;
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


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\Controller
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 *
 * @Route("/phpbb_bridge")
 */
class ConnectController extends Controller
{
    /**
     * @var FrontendIndex null
     */
    protected $frontendIndex = null;


    /**
     * Call this function to validate the incoming request
     */
    protected function validateRequest() {
        $this->container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $this->container->get('contao.framework')->initialize(); // we need to do this for autoloading contao classes
        $req = $this->container->get('request');
        /* @var $req Request */

        // Only requests from the bridge itself are allowed. Check if the specific header is set
        if($req->headers->get('x-requested-with') != 'ContaoPhpbbBridge') {
            System::log('Not allowed to access phpbb bridge', __METHOD__, TL_ERROR);
            //throw new AccessDeniedException('Not allowed to access phpbb bridge');
        }
        // The bridge also always sets a internal proxy header
        if(!$req->headers->get('x-forwarded-for')) {
            System::log('Not allowed to access phpbb bridge without proxy header', __METHOD__, TL_ERROR);
            //throw new AccessDeniedException('Not allowed to access phpbb bridge without proxy header');
        }
        $req->attributes->set('isInternalForumRequest', true);

        // Contao 4.1 currently does not support proxy headers so we've to overwrite the client ip with the true one
        if($req->headers->get('x-forwarded-for')){
            $clientIP = explode(", ",$req->headers->get('x-forwarded-for'))[0];
        } else {
            $clientIP = $req->getClientIp();
        }

        // Add the real client Ip to the trusted proxies one
        $req->setTrustedProxies(array($clientIP));
        // x-forward-for header is not supported yet, so we overwrite the client Ip manually
        // @todo report bug report
        Environment::set('ip', $clientIP);
        $this->frontendIndex = new FrontendIndex();

    }

    /**
     *
     * @Route("/test")
     */
    public function test(){
//        $this->validateRequest();
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
     *
     * @todo implement security to avoid brute force etc
     * @todo implement authentication to access API (token, digest, basic...). Maybe not needed since we recheck credentials during login process against phpbb
     *
     */
    public function loginAction(){
        $this->validateRequest();
        //dump(Config::get('disableIpCheck'));

        $user = FrontendUser::getInstance();
        $result = $user->login();

        $response = new JsonResponse();
        $response->setData(array(
           'login_status' => $result
        ));

        return $response;
    }

    /**
     * Logout a user from contao via incoming phpbb logout request
     *
     * @Route("/logout")
     */
    public function logoutAction(){
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
    public function layoutAction(){
        $this->validateRequest();

        $objPage = PageModel::findOneByType('phpbb_forum');
        Environment::set('relativeRequest', $objPage->alias);
        $response = $this->frontendIndex->run();
        if($objPage instanceof  PageModel){
            $page = new Forum();
            Input::setGet('format', 'json');
            $response = $page->getResponse($objPage);
        }
        return $response;
    }

}