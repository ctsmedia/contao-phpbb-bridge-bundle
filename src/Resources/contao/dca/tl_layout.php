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

$GLOBALS['TL_DCA']['tl_layout']['config']['onsubmit_callback'][] = array('tl_layout_phpbbforum', 'onSubmitCallback');

class tl_layout_phpbbforum extends tl_layout {

    public function onSubmitCallback(){
        Message::addInfo("phpBB Bridge: Regenerating Forum Layout Files.");
        System::getContainer()->get('phpbb_bridge.connector')->generateForumLayoutFiles();
    }


}