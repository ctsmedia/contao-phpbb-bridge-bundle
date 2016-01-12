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
use Buzz\Listener\CookieListener;
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
        return false;
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
            'viewonline' => 0
        );

        $browser = new Browser();
        $browser->addListener(new CookieListener());
        $loginResponse = $browser->submit($loginUrl, $formFields);


        dump($loginUrl);
        dump($loginResponse);
        exit;

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


}