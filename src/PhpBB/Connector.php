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

use Contao\System;
use Doctrine\DBAL\Connection;


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
    private $db;

    /**
     * @var mixed|string
     */
    private $table_prefix = '';

    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->table_prefix = System::getContainer()->getParameter('phpbb_bridge.db.table_prefix');
    }

    public function getUser($username)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->table_prefix.'users', 'pu')
            ->where('username = ?')
            ->orWhere('username_clean = ?');


        $result = $this->db->fetchAssoc($queryBuilder->getSQL(), array($username, $username));

        return $result;
    }

}