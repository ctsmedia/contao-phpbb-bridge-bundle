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


/**
 *
 * @package ctsmedia\contaophpbbbridge\contao
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class Connector
{
    protected $forum_pageId;

    public function __construct($forum_pageId)
    {
        $this->forum_pageId = $forum_pageId;
    }

    public function getForumPageId() {
        return $this->forum_pageId;
    }

    public function getContaoTemplate(){

    }

}