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

use Contao\PageModel;
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


    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->table_prefix = System::getContainer()->getParameter('phpbb_bridge.db.table_prefix');
    }

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
     * @return array
     */
    public function getConfig()
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/config/contao.yml'));
    }

    public function updateConfig(array $config)
    {
        $currentConfig = $this->getConfig();
        $isChanged = false;

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $currentConfig['parameters'])) {
                $currentConfig['parameters'][$key] = $value;
                $isChanged = true;
            }
        }

        if($isChanged === true) {
            file_put_contents(__DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/config/contao.yml', Yaml::dump($currentConfig));
        }

    }


}