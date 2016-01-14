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
$GLOBALS['TL_HOOKS']['postLogout'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoFrontendListener', 'onLogout');
$GLOBALS['TL_HOOKS']['postLogin'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoFrontendListener', 'onLogin');
$GLOBALS['TL_HOOKS']['checkCredentials'][] = array('\\Ctsmedia\\Phpbb\\BridgeBundle\\EventListener\\ContaoFrontendListener', 'onCheckCredentials');
