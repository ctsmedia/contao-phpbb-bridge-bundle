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

$GLOBALS['TL_PTY']['phpbb_forum'] = 'Ctsmedia\\Phpbb\\BridgeBundle\\PageType\\Forum';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getPageStatusIcon'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoBackendListener', 'onGetPageStatusIcon');
$GLOBALS['TL_HOOKS']['generateFrontendUrl'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoFrontendListener', 'onGenerateFrontendUrl');
$GLOBALS['TL_HOOKS']['importUser'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoFrontendListener', 'onImportUser');
$GLOBALS['TL_HOOKS']['checkCredentials'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoFrontendListener', 'onCheckCredentials');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoFrontendListener', 'onReplaceInsertTags');


/**
 * Caches
 */

// Short notation (if forum is already installed otherwise long notation (always working))
if(\Contao\System::getContainer()->has('phpbb_bridge.connector') && \Contao\System::getContainer()->get('phpbb_bridge.connector')->getForumPath()) {
    $affected =  ['web/'.\Contao\System::getContainer()->get('phpbb_bridge.connector')->getForumPath().'/ext/ctsmedia/contaophpbbbridge/styles/all/template/event'];
} else {
    $affected = ['/vendor/ctsmedia/contao-phpbb-bridge-bundle/src/Resources/phpBB/ctsmedia/contaophpbbbridge/styles/all/template/event'];
}

// Hooks for stylesheets and contao pages, that guarantees the forum layout is regenerated
// Overwrite original ones
$GLOBALS['TL_PURGE']['folders']['scripts'] = array
(
    'callback' => array('\\Ctsmedia\\Phpbb\\BridgeBundle\\Contao\\Backend\\ForumMaintenance', 'purgeScriptCache'),
    'affected' => array('assets/js', 'assets/css')
);
$GLOBALS['TL_PURGE']['folders']['pages'] = array
(
    'callback' => array('\\Ctsmedia\\Phpbb\\BridgeBundle\\Contao\\Backend\\ForumMaintenance', 'purgePageCache'),
    'affected' => array('%s/contao/html')
);

// the array is not sorted before calling in PurgeData::run
// so we know phpbb_forum job runs after it's dependencies
// you can see the order of the array also on the maintenance page itself
$GLOBALS['TL_PURGE']['folders']['phpbb_forum'] = array(
    'callback' => ['\\Ctsmedia\\Phpbb\\BridgeBundle\\Contao\\Backend\\ForumMaintenance', 'purgeForumCache'],
    'affected' => $affected
);
