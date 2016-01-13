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

namespace Ctsmedia\Phpbb\BridgeBundle\PhpBB;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Listener\CookieListener;
use Buzz\Message\RequestInterface;
use Buzz\Util\Cookie;
use Buzz\Util\CookieJar;
use Contao\Environment;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\PhpBB
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class Connector
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var mixed|string
     */
    protected $table_prefix = '';

    protected $config = null;


    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->table_prefix = System::getContainer()->getParameter('phpbb_bridge.db.table_prefix');
        $this->config = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/config/contao.yml'));
    }

    public function getCurrentUser()
    {

    }

    /**
     * Check if the current user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        $browser = $this->initForumRequest();
        $headers = $this->initForumRequestHeaders();

        // @todo load path from routing.yml
        $path = '/contao_connect/is_logged_in';
        $jsonResponse = $browser->get(Environment::get('url') . '/' . $this->getConfig('contao.forum_pageAlias') . $path, $headers);

        if($jsonResponse->getHeader('content-type') == 'application/json') {
            $result = json_decode($jsonResponse->getContent());
        } else {
            throw new \Exception("Could not communicate with forum. JSON Response expected. Got: ".$jsonResponse->getHeader('content-type'));
        }


        dump($result->data);
        dump($result->logged_in);

        return (boolean)$result->logged_in;
    }

    /**
     * Tries to login the User
     *
     * @param $username string
     * @param $password string
     */
    public function login($username, $password)
    {

        $loginUrl = Environment::get('url') . '/' . $this->getConfig('contao.forum_pageAlias') . '/ucp.php?mode=login';
        $formFields = array(
            'username' => $username,
            'password' => $password,
            'autologin' => 1,
            'viewonline' => 0,
            'login' => 'Login'
        );
        $headers = $this->initForumRequestHeaders();
        $browser = $this->initForumRequest();

        // Try to login
        // @todo maybe better login through our connector?
        $browser->submit($loginUrl, $formFields, RequestInterface::METHOD_POST, $headers);


        // Parse cookies
        $cookie_prefix = $this->getDbConfig('cookie_name');
        $loginCookies = array();
        foreach ($browser->getListener()->getCookies() as $cookie) {
            /* @var $cookie Cookie */

            // Stream cookies through to the client
            System::setCookie($cookie->getName(), $cookie->getValue(), (int)$cookie->getAttribute('expires'),
                $cookie->getAttribute('path'), $cookie->getAttribute('domain'));

            // Get phpbb cookies
            if(strpos($cookie->getName(), $cookie_prefix) !== false) {
                $loginCookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        // If we find a response cookie with user id and user id higher than 1 (anonym) everything went fine
        if($loginCookies[$cookie_prefix.'_u'] > 1){
            return true;
        }

        return false;

    }

    public function importUser($username) {
        // @todo continue here: Import phpbb user to contao
    }

    /**
     * Retrieves a users data from phpbb
     *
     * @param $username
     * @return array
     */
    public function getUser($username)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->table_prefix . 'users', 'pu')
            ->where('username = ?')
            ->orWhere('username_clean = ?');


        $result = $this->db->fetchAssoc($queryBuilder->getSQL(), array($username, $username));

        return $result;
    }

    /**
     * Retrieves a config value from the phpbb config table
     * For Example the cookie_name
     *
     * @param $key
     * @return array
     */
    public function getDbConfig($key)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('config_value')
            ->from($this->table_prefix . 'config', 'co')
            ->where('config_name = ?');

        $result = $this->db->fetchAssoc($queryBuilder->getSQL(), array($key));

        return $result;
    }

    /**
     * Returns specific config key
     *
     * @return mixed|null
     */
    public function getConfig($key)
    {
        if (array_key_exists($key, $this->config['parameters'])) {
            return $this->config['parameters'][$key];
        }
        return null;
    }


    public function updateConfig(array $config)
    {
        $currentConfig = $this->config;
        $isChanged = false;

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $currentConfig['parameters'])) {
                $currentConfig['parameters'][$key] = $value;
                $isChanged = true;
            }
        }

        if ($isChanged === true) {
            file_put_contents(__DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/config/contao.yml',
                Yaml::dump($currentConfig));
        }

    }

    /**
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
        $req = System::getContainer()->get('request');
        $headers = array();
        if ($req->headers->get('user-agent')) {
            $headers[] = 'User-Agent: ' . $req->headers->get('user-agent');
        }
        if ($req->headers->get('x-forwarded-for')) {
            $headers[] = 'X-Forwarded-For: ' . $req->headers->get('x-forwarded-for') . ', ' . Environment::get('server');
        }
        if ($req->headers->get('cookie')) {
            $headers[] = 'Cookie: ' . $req->headers->get('cookie');
        }
        if ($req->headers->get('referer')) {
            $headers[] = 'Referer: ' . $req->headers->get('referer');
        }

        return $headers;
    }


}