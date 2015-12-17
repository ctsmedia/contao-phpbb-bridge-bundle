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

$GLOBALS['TL_DCA']['tl_page']['palettes']['phpbb_forum'] = '{title_legend},title,type;{phpbb
_legend},phpbb_alias,phpbb_path;{layout_legend:hide},includeLayout;cssClass;{tabnav_legend:hide},tabindex,accesskey;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['fields']['phpbb_alias'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_page']['phpbb_alias'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'search'                  => false,
    'eval'                    => array('rgxp'=>'folderalias', 'doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50', 'mandatory' => true),
    'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_page']['fields']['phpbb_path'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_page']['phpbb_path'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'search'                  => false,
    'eval'                    => array('rgxp'=>'folderalias', 'doNotCopy'=>true, 'maxlength'=>256, 'tl_class'=>'w50', 'mandatory' => true),
    'sql'                     => "varchar(256) COLLATE utf8_bin NOT NULL default ''",
    'save_callback' => array
    (
        array('tl_page_phpbbforum', 'generatePhpbbLink')
    ),
);

class tl_page_phpbbforum extends tl_page {

    public function generatePhpbbLink($varValue, DataContainer $dc){

        if(is_link($dc->activeRecord->phpbb_alias) && readlink($dc->activeRecord->phpbb_alias) == $varValue) {
            Message::addInfo("Path to forum already set");
            $this->updateConfig($dc->activeRecord);
            $this->generateForumlayout($dc->activeRecord);
            return $varValue;
        }

        if(is_link($dc->activeRecord->phpbb_alias)  !== false && readlink($dc->activeRecord->phpbb_alias) != $varValue) {
            Message::addInfo("Removing old link");
            unlink($dc->activeRecord->phpbb_alias);
        }

        Message::addInfo("Trying to set Forum Symlink");
        if(file_exists($varValue . "/viewtopic.php")){
            Message::addInfo("Forum found. Setting Link");
            $result = symlink($varValue, $dc->activeRecord->phpbb_alias);
            if($result === true) {
                Message::addInfo("Link Set");
            }

            if(!is_link($dc->activeRecord->phpbb_alias . '/ext/ctsmedia') ||
                readlink($dc->activeRecord->phpbb_alias . '/ext/ctsmedia') != "../../contao/vendor/ctsmedia/contao-phpbb-bridge-bundle/src/Resources/phpBB/ctsmedia" ) {
                Message::addInfo("Setting Vendor Link");
                symlink("../../contao/vendor/ctsmedia/contao-phpbb-bridge-bundle/src/Resources/phpBB/ctsmedia", $dc->activeRecord->phpbb_alias . '/ext/ctsmedia');
            }

            Message::addInfo("Please activate the contao extension in the phpbb backend");
        } else {
            //Message::addError("Forum could not be found: ".$varValue . "/viewtopic.php");
            throw new Exception("Forum could not be found: ".$varValue . "/viewtopic.php");
        }

        $this->updateConfig($dc->activeRecord);
        $this->generateForumlayout($dc->activeRecord);

        return $varValue;
    }

    protected function updateConfig($activeRecord) {
        Message::addInfo("Updating Config");
        $row = $activeRecord->row();
        $row['skipInternalHook'] = true;
        $url = Controller::generateFrontendUrl($row);
        System::getContainer()->get('phpbb_bridge.connector')->updateConfig(array(
            'contao.forum_pageId' => $activeRecord->id,
            'contao.forum_pageUrl' => $url,
        ));
    }


    protected function generateForumlayout($activeRecord) {
        Message::addInfo("Generating Layout");

        $row = $activeRecord->row();
        $row['skipInternalHook'] = true;
        $url = Controller::generateFrontendUrl($row, null, null, false);

        $frontendRequest = new \Contao\Request();
        $frontendRequest->send(Environment::get('host').'/'.$url);



    }

}