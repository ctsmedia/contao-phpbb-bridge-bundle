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

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Listener\CookieListener;
use Buzz\Message\RequestInterface;
use Buzz\Message\Response;
use Buzz\Util\Cookie;
use Buzz\Util\CookieJar;
use phpbb\auth\auth;
use phpbb\request\request;
use phpbb\user;

require_once __DIR__ . "/../vendor/autoload.php";

/**
 *
 * @package ctsmedia\contaophpbbbridge\contao
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class Connector
{
    protected $isBridgeInstalled;
    protected $forum_pageId;
    protected $contao_url;

    protected $user;

    protected $auth;

    protected $request;

    public function __construct(
        $isBridgeInstalled,
        $forum_pageId,
        $contao_url,
        user $user,
        auth $auth,
        request $request
    ) {
        $this->isBridgeInstalled = (bool)$isBridgeInstalled;
        $this->forum_pageId = $forum_pageId;
        $this->contao_url = $contao_url;
        $this->user = $user;
        $this->auth = $auth;
        $this->request = $request;
    }

    /**
     * Checks if the bridge is mark as installed on phpbb side
     *
     * @return bool
     */
    public function isInstalled()
    {
        return $this->isBridgeInstalled;
    }

    public function test()
    {
        $browser = $this->initContaoRequest();
        $headers = $this->initContaoRequestHeaders();
        $response = $browser->get($this->contao_url . '/phpbb_bridge/test', $headers);

        //dump($response->getContent());
        echo $response->getContent();
        exit;

    }

    /**
     * Send a login request to contao
     *
     * @param $username
     * @param $password
     * @return bool true if the login was successful
     */
    public function login($username, $password, $autologin = false)
    {

        // The request comes from contao. Maybe from a hook like credentialCheck, importUser so we skip
        if ($this->request->header('X-Requested-With') == 'ContaoPhpbbBridge') {
            return false;
        };

        // Init request
        $browser = $this->initContaoRequest();
        $headers = $this->initContaoRequestHeaders();
        $formFields = array(
            'username' => $username,
            'password' => $password,
            'autologin' => (bool)$autologin,
        );

        // Send request as form data
        /* @var $response Response */
        $response = $browser->submit($this->contao_url . "/phpbb_bridge/login", $formFields,
            RequestInterface::METHOD_POST, $headers);

        if ($this->isJsonResponse($response)) {
            $jsonData = json_decode($response->getContent());

            if (isset($jsonData->login_status) && $jsonData->login_status == true) {

                // Set cookies from the contao response
                if ($response->getHeader('set-cookie')) {

                    $delimiter = ' || ';
                    $cookies = explode($delimiter, $response->getHeader('set-cookie', $delimiter));

                    foreach ($cookies as $cookie) {
                        header('Set-Cookie: ' . $cookie, false);
                    }

                    // The following won't work because the expire value is not an int and conversion something like
                    // 16-Jan-2016 18:07:35 GMT to an int is really unnecessary overhead
                    // although it's looks cleaner at first like above solution
                    // $cookieJar = new CookieJar();
                    // $cookieJar->processSetCookieHeaders($browser->getLastRequest(), $response);
                    // foreach($cookieJar->getCookies() as $cookie) {
                    //      setcookie($cookie->getName(), $cookie->getValue(), $cookie->getAttribute('expires'), $cookie->getAttribute('path'), $cookie->getAttribute('domain'), $cookie->getAttribute('secure'),$cookie->getAttribute('httponly'));
                    // }                  }
                }
                return true;
            }
        }
        return false;
    }


    /**
     * Send a logout request to contao
     *
     * @return bool if the logout was successful
     */
    public function logout()
    {

        // The request comes from contao. We can skip here
        if ($this->request->header('X-Requested-With') == 'ContaoPhpbbBridge') {
            return false;
        };

        $browser = $this->initContaoRequest();
        $headers = $this->initContaoRequestHeaders();

        // This is usually a fire and forget request
        // but on contao side we return a Json response for debugging purposes
        $response = $browser->get($this->contao_url . '/phpbb_bridge/logout', $headers);

        if ($this->isJsonResponse($response)) {
            $jsonData = json_decode($response->getContent());
            if (isset($jsonData->logout_status)) {
                // Set cookies from the contao response
                if ($response->getHeader('set-cookie')) {

                    $delimiter = ' || ';
                    $cookies = explode($delimiter, $response->getHeader('set-cookie', $delimiter));

                    foreach ($cookies as $cookie) {
                        header('Set-Cookie: ' . $cookie, false);
                    }
                }
                return $jsonData->logout_status;
            }
        }
        return false;
    }

    /**
     * Returns the layout sections
     *
     * @return array
     */
    public function loadLayout()
    {
        $browser = $this->initContaoRequest();
        $headers = $this->initContaoRequestHeaders();

        /* @var $response Response */
        $response = $browser->get($this->contao_url . '/phpbb_bridge/layout', $headers);

        // Maybe we get asked to refresh the current site. This can happen if an session is expired and autologin is triggered
        // Wo do this one time
        // @see Contao/FrontendUser::authenticate() => Controller::reaload()
        if ($response->getStatusCode() == 303
            && $response->getHeader('Location') == $this->contao_url . '/phpbb_bridge/layout'
            && $response->getHeader('set-cookie')
        ) {
            $delimiter = ' || ';
            $cookies = explode($delimiter, $response->getHeader('set-cookie', $delimiter));
            foreach ($cookies as $cookie) {
                header('Set-Cookie: ' . $cookie, false); // First we put the cookies into the response to the client
                // we expect a FE_AUTH cookie which will get set for new requests automatically via the cookie listener
            }
            $response = $browser->get($this->contao_url . '/phpbb_bridge/layout', $headers);
        }



        $sections = array();
        if ($this->isJsonResponse($response)) {
            $sections = $jsonData = json_decode($response->getContent());

        } 
        return $sections;
    }

    /**
     *
     * This code is copied from Ctsmedia\Phpbb\BridgeBundle\PhpBB\Connector
     * @return Browser
     */
    protected function initContaoRequest()
    {
        // Init Request
        $client = new Curl();
        $client->setMaxRedirects(0);
        $browser = new Browser();
        $browser->setClient($client);
        $cookieListener = new CookieListener();
        $browser->addListener($cookieListener);

        return $browser;
    }

    /**
     * Parse current request and build forwarding headers
     * @return array
     */
    protected function initContaoRequestHeaders()
    {
        $headers = array();
        if ($this->request->header('User-Agent')) {
            $headers[] = 'User-Agent: ' . $this->request->header('User-Agent');
        }
        // Set the forward header. Our own IP gets added automatically (by the client?)
        if ($this->request->header('X-Forwarded-For')) {
            $headers[] = 'X-Forwarded-For: ' . $this->request->header('X-Forwarded-For');
        } else {
            $headers[] = 'X-Forwarded-For: ' . $this->request->server('REMOTE_ADDR');
        }
        if ($this->request->header('Cookie')) {
            $headers[] = 'Cookie: ' . $this->request->header('Cookie');
        }
        if ($this->request->header('Referer')) {
            $headers[] = 'Referer: ' . $this->request->header('Referer');
        }

        // Add a special header (usually used for ajax but context is correct here)
        // so we can set a flag to not trigger an endless request loop
        // because contao hooks trigger requests to phpbb on contao login for example
        $headers[] = 'X-Requested-With: ContaoPhpbbBridge';

        return $headers;
    }

    /**
     * Checks if we had a successfull response with Json content in it
     *
     * @param Response $response
     * @return bool
     */
    protected function isJsonResponse(Response $response)
    {
        return $response->getStatusCode() == 200 && $response->getHeader('content-type') == 'application/json';
    }

}