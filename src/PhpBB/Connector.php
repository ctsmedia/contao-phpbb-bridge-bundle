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

namespace Ctsmedia\Phpbb\BridgeBundle\PhpBB;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Listener\CookieListener;
use Buzz\Message\RequestInterface;
use Buzz\Message\Response;
use Buzz\Util\Cookie;
use Contao\Encryption;
use Contao\Environment;
use Contao\FrontendUser;
use Contao\Input;
use Contao\MemberModel;
use Contao\Message;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Yaml\Yaml;


/**
 * Main class to handle phpbb related actions
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\PhpBB
 * @author Daniel Schwiperich <https://github.com/DanielSchwiperich>
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

    protected $debug = false;


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
        if($this->debug) System::log("phpbb_bridge: ".__METHOD__, __METHOD__, TL_ACCESS);

        // unset any session data if the session does not belong to a member
        if(!FE_USER_LOGGED_IN) {
            System::getContainer()->get('session')->set('phpbb_user', null);
        }

        // Checks session if user data is already initialized (and not anonym user) or tries to check status (which then set user data to session)
        if ( FE_USER_LOGGED_IN && System::getContainer()->get('session')->get('phpbb_user', null) === null) {
            $user = FrontendUser::getInstance();

            // Test if user is member of forum groups. Only throws a warning atm
            // @todo Make being member of forum group mandatory?
            $isForumMember = false;
            foreach($this->getForumMemberGroupIds() as $groupId){
                if($user->isMemberOf($groupId)) {
                    $isForumMember = true;
                    continue;
                }
            }

            $phpbbUser = $this->getUser($user->username);

            // Throw warning if a adequate phpbb user was found but the contao member is not member of forum groups
            if($phpbbUser !== null && $isForumMember === false) {
                System::log('Warning: Found Forum user but not being member of forum member groups. Maybe reassign or clear', __METHOD__, TL_ERROR);
            }

            System::getContainer()->get('session')->set('phpbb_user', $phpbbUser);

        }
        return System::getContainer()->get('session')->get('phpbb_user', null);

    }

    /**
     * Retrieves a users data from phpbb
     *
     *      $phpbbuser = System::getContainer()->get('phpbb_bridge.connector')->getUser('name_of_user');
     *      echo $phpbbuser->user_id
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
     * Retrieves User Profile Data by a given phpbb userId
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
     * Tries to login the User tp phpbb
     *
     *
     * @param $username string
     * @param $password string Plain Password
     * @return bool
     */
    public function validateLogin($username, $password)
    {
        if($this->debug) System::log("phpbb_bridge: ".__METHOD__, __METHOD__, TL_ACCESS);
        
        $browser = $this->initForumRequest(true);
        $headers = $this->initInternalForumRequestHeaders();

        $path = sprintf('/contao_connect/is_valid_login/%s/%s', urlencode($username), urlencode($password));
        $response = $browser->get($this->getBridgeConfig('url') . '/' . $this->getForumPath() . $path, $headers);

        if($this->isJsonResponse($response)) {
            $jsonData = json_decode($response->getContent());

            if($jsonData->status === true) {
                System::log('Login Test successfull for ' . $username, __METHOD__, TL_ACCESS);
            } else {
                System::log('Login Test failed for ' . $username, __METHOD__, TL_ACCESS);
            }

            return $jsonData->status;
        }

        System::log('Could not get Response for login test for ' . $username. ' || '.$response->getStatusCode().' || '.$response->getContent(), __METHOD__, TL_ERROR);

        return false;
    }

    /**
     * Logout from phpbb
     */
    public function logout()
    {
        if($this->debug) System::log("phpbb_bridge: ".__METHOD__, __METHOD__, TL_ACCESS);
        
        $cookie_prefix = $this->getDbConfig('cookie_name');
        $sid = Input::cookie($cookie_prefix . '_sid');

        System::getContainer()->get('session')->remove('phpbb_user');

        if ($sid) {
            $logoutUrl = Environment::get('url') . '/' . $this->getForumPath() . '/contao_connect/logout';
            $headers = $this->initForumRequestHeaders();
            $browser = $this->initForumRequest();
            $browser->get($logoutUrl, $headers);

            // Parse cookies and send them to the client
            foreach ($browser->getListener()->getCookies() as $cookie) {
                /* @var $cookie Cookie */

                // Stream cookies through to the client
                System::setCookie($cookie->getName(), $cookie->getValue(), strtotime($cookie->getAttribute('expires')),
                    $cookie->getAttribute('path'), $cookie->getAttribute('domain'));
            }
        }

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
        if($this->debug) System::log("phpbb_bridge: ".__METHOD__, __METHOD__, TL_ACCESS);

        // Find User in forum
        $user = $this->getUser($username);

        if ($user) {

            System::log('Importing User ' . $username, __METHOD__, TL_ACCESS);

            // Try to find user by real username if he entered username_clean
            // he may not be imported yet with it's clean username
            $contaoUser = MemberModel::findByUsername($user->username);
            if(null == $contaoUser){
                $contaoUser = new MemberModel();
            }

            $contaoUser->username = $user->username;
            $contaoUser->username_clean = $user->username_clean;
            $contaoUser->email = $user->user_email;
            $contaoUser->firstname = 'Vorname';
            $contaoUser->lastname = 'Nachname';
            $contaoUser->password = Encryption::hash($password);
            $contaoUser->login = 1;
            $contaoUser->tstamp = $contaoUser->dateAdded = time();

            $contaoUser->groups = $this->getForumMemberGroupIds(true);


            // @todo add try catch, make it more safe, logout phpbb user on fail?
            $contaoUser->save();
            System::log('User imported: ' . $contaoUser->username, __METHOD__, TL_ACCESS);

            // username_clean used for login
            if($username != $contaoUser->username){
                Input::setPost('username', $contaoUser->username);
            }

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
     * Enforce those settings to make the bridge runnable
     */
    public function setMandatoryDbConfigValues(){
        // Enforce the forwared_for_check to 0, because we switching from live accessing the forum (no proxy)
        // to internal requests via contao all the time
        $this->updateDbConfig('forwarded_for_check', 0);
        $this->updateDbConfig('ip_check', 0);
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
        if($this->debug) System::log("phpbb_bridge: ".__METHOD__, __METHOD__, TL_ACCESS);
        $browser = $this->initForumRequest();
        $headers = $this->initForumRequestHeaders();

        // @todo load path from routing.yml?
        $path = '/contao_connect/purge_cache';
        $browser->get($this->getBridgeConfig('url') . '/' . $this->getForumPath() . $path, $headers);
    }


    /**
     * Regenerates the Forum Layout
     */
    public function generateForumLayoutFiles(){
        $url = $this->getBridgeConfig('forum_pageUrl');
        if($url !== null) {
            $frontendRequest = new \Contao\Request();
            $frontendRequest->send($url);
        }
    }


    /**
     * Retrieves the member groups id for Forum members
     * New members from phpbb get imported to these groups
     *
     * @param bool $raw
     * @return mixed|null
     */
    public function getForumMemberGroupIds($raw = false) {
        $objPage = PageModel::findOneByType('phpbb_forum');
        if($objPage->phpbb_default_groups){
            return ($raw === true ) ? $objPage->phpbb_default_groups : deserialize($objPage->phpbb_default_groups);
        }

        return ($raw === true ) ? null : [];
    }

    /**
     * Compares the current host with phpbb cookie Domain
     * @return bool
     */
    public function compareCookieDomains() {
        $host = Environment::get('host');
        $phpbbCookieDomain = $this->getDbConfig('cookie_domain');

        return strcasecmp($host, $phpbbCookieDomain) == 0;

    }

    /**
     * Compares the current host to the phpbb Cookie Domain
     * and adds Warning Msg if they differ
     */
    public function testCookieDomain(){
        $result = $this->compareCookieDomains();

        if($result === false){
            Message::addError('WARNING: The current Host ('.Environment::get('host').') differs from the cookie_domain configured in phpbb. Please make sure the used frontend domain is the same to: '.
                System::getContainer()->get('phpbb_bridge.connector')->getDbConfig('cookie_domain')
            );
        }
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
        if ($force === false
            && System::getContainer()->get('request_stack')->getCurrentRequest()
            && System::getContainer()->get('request_stack')->getCurrentRequest()->headers->get('x-requested-with') == 'ContaoPhpbbBridge') {
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
        $headers = array();

        if(System::getContainer()->get('request_stack')->getCurrentRequest()) {
            $req = System::getContainer()->get('request_stack')->getCurrentRequest();

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
        }

        $headers[] = 'X-Requested-With: ContaoPhpbbBridge';

        return $headers;
    }

    /**
     * Set header for only internal bridge requests
     * @return array
     */
    protected function initInternalForumRequestHeaders()
    {
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