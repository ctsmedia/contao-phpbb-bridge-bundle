<?php
/*
 * This file is part of contao-phpbb-bridge-bundle
 * 
 * Copyright (c) 2015-2016 Daniel Schwiperich
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    'CONTAO_LOGIN_FAILED'	=> 'Could not log in to Contao. Please retry or contact support.',
    'CONTAO_LOGIN_LOCKED'	=> 'Your account was temporary locked. You\'ve to wait some minutes before you can retry. ',
));
