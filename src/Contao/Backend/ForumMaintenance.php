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

namespace Ctsmedia\Phpbb\BridgeBundle\Contao\Backend;

use Contao\Automator;
use Contao\System;


/**
 *
 * Maintenance Collection for forum, mainly contao backend
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\Contao\Backend
 * @author Daniel Schwiperich <https://github.com/DanielSchwiperich>
 */
class ForumMaintenance extends System
{

    /**
     * Purges the forum caches
     */
    public function purgeForumCache(){
        System::getContainer()->get('phpbb_bridge.connector')->generateForumLayoutFiles();
        System::getContainer()->get('phpbb_bridge.connector')->clearForumCache();

        $this->log('Purged the phpbb forum cache', __METHOD__, TL_CRON);
    }

    /**
     * Overwrite for Automator::purgeScriptCache()
     * Makes sure the forum layout is regenerated
     *
     */
    public function purgeScriptCache() {
        $automator = new Automator();
        $automator->purgeScriptCache();
        System::getContainer()->get('phpbb_bridge.connector')->generateForumLayoutFiles();
        $this->log('Purged the phpbb forum cache', __METHOD__, TL_CRON);
    }

    /**
     * Overwrite for Automator::purgePageCache
     * Makes sure the forum layout is regenerated
     */
    public function purgePageCache() {
        $automator = new Automator();
        $automator->purgePageCache();
        System::getContainer()->get('phpbb_bridge.connector')->generateForumLayoutFiles();
        $this->log('Purged the phpbb forum cache', __METHOD__, TL_CRON);
    }


}