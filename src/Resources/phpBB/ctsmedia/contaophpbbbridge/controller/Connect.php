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
use Monolog\Handler\FingersCrossedHandler;
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

    protected $debug = true;
    protected $logger = null;

    public function __construct(
        config $config,
        ContainerInterface $container,
        dispatcher $dispatcher,
        user $user,
        Connector $contaoConnector,
        $root_path,
        $php_ext,
        \phpbb\auth\provider\db $db_auth
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->user = $user;
        $this->contaoConnector = $contaoConnector;
        $this->rootPath = $root_path;
        $this->phpExt = $php_ext;
        $this->db_auth = $db_auth;

        $this->logger = new Logger('bridge_controller');
        $this->logger->pushHandler(
            new FingersCrossedHandler(new StreamHandler(__DIR__.'/../bridge_error.log'), Logger::ERROR)
        );
        $this->logger->pushHandler(new StreamHandler(__DIR__.'/../bridge.log', Logger::DEBUG));
    }

    /**
     * Kills existings sessions if a user was found
     *
     * path: /contao_connect/logout
     *
     * @return JsonResponse
     */
    public function logout()
    {
        if ($this->debug) {
            $this->logger->debug(__METHOD__);
        }

        $status = false;
        if ($this->contaoConnector->isLoggedIn()) {
            $status = $this->user->session_kill(false);
        }

        return new JsonResponse(['status' => $status]);

    }

    /**
     * Test if a valid db login can be made with given credentials
     *
     * path: /contao_connect/is_valid_login/{username}/{password}
     *
     * @param $username
     * @param $password
     * @return JsonResponse
     * @throws \Exception
     */
    public function isValidLogin($username, $password)
    {
        $status = false;

        // We only allow internal requests from Contao
        if ($this->container->get('request')->header('X-Requested-With') == 'ContaoPhpbbBridge') {

            $username = urldecode($username);
            $password = urldecode($password);

            if ($this->debug) {
                $this->logger->debug(__METHOD__, array($username, substr($password, 0, 4).'...'));
            }

            // Get user and decrease login attempt. This one here should not count
            $username_clean = utf8_clean_string($username);
            $sql = 'SELECT * FROM '.USERS_TABLE."
			WHERE username_clean = '".$this->container->get('dbal.conn')->sql_escape($username_clean)."'";
            $result = $this->container->get('dbal.conn')->sql_query($sql);
            $row = $this->container->get('dbal.conn')->sql_fetchrow($result);
            $this->container->get('dbal.conn')->sql_freeresult($result);

            // Only test if a user was found
            if ($row['user_id'] > ANONYMOUS) {
                // test against a db login
                $result = $this->db_auth->login($username, $password);
                if ($result['status'] == LOGIN_SUCCESS) {
                    $status = true;
                } else {
                    // Set the login attempts back to what it was before.
                    $sql = 'UPDATE '.USERS_TABLE.' SET user_login_attempts = '.(int)$row['user_login_attempts'].' 
			        WHERE user_id = '.(int)$row['user_id'];
                    $this->container->get('dbal.conn')->sql_query($sql);
                }
            }
        };

        $response = new JsonResponse();
        $response->setData(array('status' => $status));

        return $response;
    }

    /**
     * Purges the phpbb cache
     *
     * path: /contao_connect/purge_cache
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function purgeCache()
    {
        $response = new JsonResponse();
        $status = true;
        $data = [];

        try {
            $this->container->get('cache')->purge();
        } catch (Exception $e) {
            $status = false;
            $data['error_message'] = $e->getMessage();
        }

        $response->setData(
            array(
                'status' => $status,
                'data' => $data,
            )
        );

        return $response;
    }

    /**
     * Test the API and return installation status
     *
     * path: /test
     *
     * @return JsonResponse
     */
    public function test()
    {
        $response = new JsonResponse();
        $response->setData(
            array(
                'status' => $this->contaoConnector->isInstalled(),
                'data' => [],
            )
        );

        return $response;
    }

}