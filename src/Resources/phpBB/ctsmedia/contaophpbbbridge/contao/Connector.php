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
    protected $forum_pageId;

    protected $user;

    protected $auth;

    protected $request;

    public function __construct($forum_pageId, user $user, auth $auth, request $request)
    {
        $this->forum_pageId = $forum_pageId;
        $this->user = $user;
        $this->auth = $auth;
        $this->request = $request;
    }

    /**
     * @param $username
     * @param $password
     * @param bool $force Will force contao to login the user even if the password does not match
     */
    public function login($username, $password, $force = false) {
        // @todo implement, continue here, but first build an entry point / controller on contao side

    }

    public function logout(){
        // @todo implement, continue here, but first build an entry point / controller on contao side
    }

    /**
     *
     * This code is copied from Ctsmedia\Phpbb\BridgeBundle\PhpBB\Connector
     * @return Browser
     */
    protected function initForumRequest()
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
    protected function initForumRequestHeaders()
    {
        $headers = array();
        if ($this->request->header('User-Agent')) {
            $headers[] = 'User-Agent: ' . $this->request->header('User-Agent');
        }
        if ($this->request->header('X-Forwarded-For')) {
            $headers[] = 'X-Forwarded-For: ' . $this->request->header('X-Forwarded-For') . ', ' . Environment::get('server');
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

}