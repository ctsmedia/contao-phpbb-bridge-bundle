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
use Contao\Encryption;
use Contao\Environment;
use Contao\Input;
use Contao\MemberModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
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
        $configFile = __DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/config/contao.yml';
        if (is_file($configFile)) {
            $this->config = Yaml::parse(file_get_contents($configFile));
        } else {
            $this->config = Yaml::parse(file_get_contents($configFile . '.dist'));
        }

    }

    /**
     * Retrieves the currently logged in user
     *
     * Usage:
     *
     *      $phpbbuser = System::getContainer()->get('phpbb_bridge.connector')->getCurrentUser();
     *      echo $phpbbuser->username
     *      echo $phpbbuser->user_email
     *      echo $phpbbuser->user_birthday
     *
     * @todo Should we check if frontend user is also logged in on contao side?
     *
     * @return object|null
     * @throws \Exception
     */
    public function getCurrentUser()
    {
        // Checks session if user data is alreay initialized or tries to check status (which then set user data to session)
        if (System::getContainer()->get('session')->get('phpbb_user') || $this->isLoggedIn()) {
            return System::getContainer()->get('session')->get('phpbb_user');
        }
        return null;

    }

    /**
     * Retrieves a users data from phpbb
     *
     *      $phpbbuser = System::getContainer()->get('phpbb_bridge.connector')->getUser('name_of_user');
     *      echo $phpbbuser->username
     *      echo $phpbbuser->user_email
     *      echo $phpbbuser->user_birthday
     *
     * @param $username
     * @return object|null
     */
    public function getUser($username)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->table_prefix . 'users', 'pu')
            ->where('username = ?')
            ->orWhere('username_clean = ?');


        $result = $this->db->fetchAssoc($queryBuilder->getSQL(), array($username, $username));

        if ($result) {
            $result = (object)$result;
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Retrieves User Profile Data by a given userId
     *
     * @param int $userId
     * @return object|null
     */
    public function getUserProfile($userId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->table_prefix . 'profile_fields_data', 'pf')
            ->where('user_id = ?');

        $result = $this->db->fetchAssoc($queryBuilder->getSQL(), array($userId));

        if ($result) {
            $result = (object)$result;
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Check if the current user is logged in and append data to session
     *
     * @todo Implement caching?
     * @return bool
     * @throws \Exception
     */
    public function isLoggedIn()
    {
        $browser = $this->initForumRequest();
        $headers = $this->initForumRequestHeaders();

        // @todo load path from routing.yml
        $path = '/contao_connect/is_logged_in';
        $jsonResponse = $browser->get(Environment::get('url') . '/' . $this->getForumPath() . $path, $headers);

        if ($jsonResponse->getHeader('content-type') == 'application/json') {
            $result = json_decode($jsonResponse->getContent());
        } else {
            System::log("Could not communicate with forum. JSON Response expected. Got: " . $jsonResponse->getHeader('content-type'),
                __METHOD__, TL_ERROR);
            throw new \Exception("Could not communicate with forum. JSON Response expected. Got: " . $jsonResponse->getHeader('content-type'));
        }


        System::getContainer()->get('session')->set('phpbb_user', $result->data);


        return (boolean)$result->logged_in;
    }

    /**
     * Logout from phpbb
     */
    public function logout()
    {
        $cookie_prefix = $this->getDbConfig('cookie_name');
        $sid = Input::cookie($cookie_prefix . '_sid');

        System::getContainer()->get('session')->remove('phpbb_user');

        if ($sid) {
            $logoutUrl = Environment::get('url') . '/' . $this->getForumPath() . '/ucp.php?mode=logout&sid=' . $sid;
            $headers = $this->initForumRequestHeaders();
            $browser = $this->initForumRequest();
            $browser->get($logoutUrl, $headers);
        } else {
            System::log("Invalid try to logout user. No active session found.", __METHOD__, TL_ACCESS);
        }

    }

    /**
     * Tries to login the User
     *
     * !!Only returns true if the user is not alreay logged in!!
     * @todo autologin / rememberme sync
     *
     * @param $username string
     * @param $password string
     * @return bool
     */
    public function login($username, $password, $autologin = false, $forceToSend = false)
    {
        // @todo login againt bridge controller
        $loginUrl = Environment::get('url') . '/' . $this->getForumPath() . '/ucp.php?mode=login';
        $formFields = array(
            'username' => $username,
            'password' => $password,
            'autologin' => (bool)$autologin,
            'viewonline' => 0,
            'login' => 'Login'
        );
        $headers = $this->initForumRequestHeaders();
        $browser = $this->initForumRequest($forceToSend);

        // Try to login
        // @todo maybe better login through our connector?
        $response = $browser->submit($loginUrl, $formFields, RequestInterface::METHOD_POST, $headers);


        // Parse cookies
        $cookie_prefix = $this->getDbConfig('cookie_name');
        $loginCookies = array();
        foreach ($browser->getListener()->getCookies() as $cookie) {
            /* @var $cookie Cookie */

            // Stream cookies through to the client
            System::setCookie($cookie->getName(), $cookie->getValue(), (int)$cookie->getAttribute('expires'),
                $cookie->getAttribute('path'), $cookie->getAttribute('domain'));

            // Get phpbb cookies
            if (strpos($cookie->getName(), $cookie_prefix) !== false) {
                $loginCookies[$cookie->getName()] = $cookie->getValue();
            }
        }


        // If we find a response cookie with user id and user id higher than 1 (anonym) everything went fine
        if ($loginCookies[$cookie_prefix . '_u'] > 1) {
            System::log('Login to phpbb succeeded for ' . $username, __METHOD__, TL_ACCESS);
            return true;
        }

        System::log('Login to phpbb failed for ' . $username, __METHOD__, TL_ACCESS);
        return false;

    }

    /**
     * Imports a user from phpbb to contao
     *
     * @param $username
     * @param $password
     * @return bool
     * @throws \Exception
     */
    public function importUser($username, $password)
    {

        $user = $this->getUser($username);

        if ($user) {

            System::log('Importing User ' . $username, __METHOD__, TL_ACCESS);
            $contaoUser = new MemberModel();

            $contaoUser->username = $user->username;
            $contaoUser->email = $user->user_email;
            $contaoUser->firstname = 'Vorname';
            $contaoUser->lastname = 'Nachname';
            $contaoUser->password = Encryption::hash($password);
            $contaoUser->login = 1;
            $contaoUser->tstamp = $contaoUser->dateAdded = time();
            $contaoUser->save();
            System::log('User imported: ' . $username, __METHOD__, TL_ACCESS);
            return true;

        } else {
            System::log($username . ' could not be found in phpbb db', __METHOD__, TL_ACCESS);
            return false;
        }
    }

    /**
     * Retrieves a config value from the phpbb config table
     * For Example the cookie_name
     *
     * @param $key
     * @return mixed
     */
    public function getDbConfig($key)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('config_value')
            ->from($this->table_prefix . 'config', 'co')
            ->where('config_name = ?');

        $result = $this->db->fetchAssoc($queryBuilder->getSQL(), array($key));

        return $result['config_value'];
    }

    public function updateDbConfig($key, $value)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->update($this->table_prefix . 'config', 'co')
            ->set('config_value', $value)
            ->where('config_name = :key')
            ->setParameter('key', $key);
        $result = $queryBuilder->execute();

        return $result;

    }

    /**
     * Alias method to the forum path easily
     */
    public function getForumPath()
    {
        return $this->getBridgeConfig('forum_pageAlias');
    }

    /**
     * Returns specific config key from the bridge config
     *
     * Keys:
     * forum_pageId: PageId for the Bridge Page Type => f.e. 39
     * forum_pageUrl: Full url to the page => f.e. 'http://phpbbbridge.contao.local/39.html'
     * forum_pageAlias: Path to the forum for accessable urls f.e. 'phpbb' so the forum is accessable under 'http://phpbbbridge.contao.local/phpbb/index.php'
     * url: Main Url f.e. http://phpbbbridge.contao.local
     * body_class: String - From the layout configuration
     * load_dynamic_layout: 1|0
     *
     * @return mixed|null
     */
    public function getBridgeConfig($key)
    {
        if (array_key_exists('contao.' . $key, $this->config['parameters'])) {
            return $this->config['parameters']['contao.' . $key];
        }
        return null;
    }


    /**
     * Updates bridge config for phpbb
     *
     * @param array $config
     */
    public function updateConfig(array $config, $clearPhpbbCache = true)
    {
        $currentConfig = $this->config;
        $isChanged = false;
        $configFile = __DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/config/contao.yml';
        $distConfig = null;

        // Check for changed config values
        foreach ($config as $key => $value) {
            // test existing parameters
            if (array_key_exists($key, $currentConfig['parameters'])) {
                if ($currentConfig['parameters'][$key] != $value) {
                    $currentConfig['parameters'][$key] = $value;
                    $isChanged = true;
                }
            // if a new parameter is found, make sure it's a valid one (testing against contao.yml.dist)
            } else {
                if(!$distConfig) $distConfig = Yaml::parse(file_get_contents($configFile . '.dist'));
                if(array_key_exists($key, $distConfig['parameters'])){
                    $currentConfig['parameters'][$key] = $value;
                    $isChanged = true;
                }
            }
        }

        if ($isChanged === true) {
            $result = file_put_contents($configFile, Yaml::dump($currentConfig));

            if (!($result > 0)) {
                throw new IOException('Could not write bidge config file ' . $configFile);
            }

            // We've to load the new / updated config now for future processing
            $this->config = Yaml::parse(file_get_contents($configFile));

            if ($clearPhpbbCache === true) {
                $this->clearForumCache();
            }
        }

    }

    /**
     * Sends a request to the forum to clear its cache.
     * Useful when config has changed for example
     */
    public function clearForumCache()
    {
        $browser = $this->initForumRequest();
        $headers = $this->initForumRequestHeaders();

        // @todo load path from routing.yml?
        $path = '/contao_connect/purge_cache';
        $browser->get(Environment::get('url') . '/' . $this->getForumPath() . $path, $headers);
    }

    /**
     * @return Browser
     */
    protected function initForumRequest($force = false)
    {
        // Init Request
        $client = new Curl();
        $client->setMaxRedirects(0);
        $browser = new Browser();
        $browser->setClient($client);
        $cookieListener = new CookieListener();
        $browser->addListener($cookieListener);

        // We need to make sure that the if the original Request is already coming from the forum, we then are not
        // allowed to send a request to the forum so we create a login loop for example.
        if ($force === false && System::getContainer()->get('request')->headers->get('x-requested-with') == 'ContaoPhpbbBridge') {
            System::log('Bridge Request Recursion detected', __METHOD__, TL_ERROR);
            throw new TooManyRequestsHttpException(null, 'Internal recursion Bridge requests detected');
        }

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
            //split by comma+space
            $forwardIps = explode(", ", $req->headers->get('x-forwarded-for'));
            //add the server ip
            $forwardIps[] = Environment::get('server');
            //set X-Forwarded-For after imploding the array into a comma+space separated string
            $headers[] = 'X-Forwarded-For: ' . implode(", ", array_unique($forwardIps));
        } else {
            $headers[] = 'X-Forwarded-For: ' . Environment::get('ip') . ', ' . Environment::get('server');
        }
        if ($req->headers->get('cookie')) {
            $headers[] = 'Cookie: ' . $req->headers->get('cookie');
        }
        if ($req->headers->get('referer')) {
            $headers[] = 'Referer: ' . $req->headers->get('referer');
        }
        $headers[] = 'X-Requested-With: ContaoPhpbbBridge';

        return $headers;
    }


}