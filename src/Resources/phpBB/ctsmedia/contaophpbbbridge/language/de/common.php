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
    'CONTAO_LOGIN_FAILED'	=> 'Konnte nicht bei Contao einloggen. Bitte erneut versuchen oder Support kontaktieren.',
    'CONTAO_LOGIN_LOCKED'	=> 'Der Account wurde temporär gesperrt aufgrund zu vieler Loginversuche. Probiere es später noch einmal.',
));
