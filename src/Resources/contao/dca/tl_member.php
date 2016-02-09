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

$GLOBALS['TL_DCA']['tl_member']['fields']['username_clean'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['username_clean'],
    'exclude'                 => true,
    'search'                  => true,
    'sorting'                 => true,
    'flag'                    => 1,
    'inputType'               => 'text',
    'eval'                    => array('mandatory'=>true, 'unique'=>true, 'rgxp'=>'extnd', 'nospace'=>true, 'maxlength'=>64,),
    'sql'                     => "varchar(64) COLLATE utf8_bin NULL"
);


$newMemberListFieldsArray = [];
foreach($GLOBALS['TL_DCA']['tl_member']['list']['label']['fields'] as $value ) {
    $newMemberListFieldsArray[] = $value;
    if($value == 'username') {
        $newMemberListFieldsArray[] = 'username_clean';
    }
}
$GLOBALS['TL_DCA']['tl_member']['list']['label']['fields'] = $newMemberListFieldsArray;
