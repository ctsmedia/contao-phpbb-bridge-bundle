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

$GLOBALS['TL_DCA']['tl_page']['palettes']['phpbb_forum'] = '{title_legend},title,type;{phpbb_legend},phpbb_alias,phpbb_path,phpbb_default_groups;{layout_legend:hide},includeLayout;cssClass;{tabnav_legend:hide},tabindex,accesskey;{publish_legend},published';

$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('tl_page_phpbbforum', 'updateConfig');
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('tl_page_phpbbforum', 'generateForumLayout');

// @todo add translations and label texts
$GLOBALS['TL_DCA']['tl_page']['fields']['phpbb_alias'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_page']['phpbb_alias'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'search'                  => false,
    'eval'                    => array('rgxp'=>'folderalias', 'doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50', 'mandatory' => true),
    'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
);
// @todo add translations and label texts
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

$GLOBALS['TL_DCA']['tl_page']['fields']['phpbb_default_groups'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_page']['phpbb_default_groups'],
    'exclude'                 => true,
    'filter'                  => true,
    'inputType'               => 'checkboxWizard',
    'foreignKey'              => 'tl_member_group.name',
    'eval'                    => array('multiple'=>true, 'feEditable'=>true, 'feGroup'=>'login', 'tl_class' => 'clr'),
    'sql'                     => "blob NULL",
    'relation'                => array('type'=>'belongsToMany', 'load'=>'lazy')
);

class tl_page_phpbbforum extends tl_page {

    public function generatePhpbbLink($varValue, DataContainer $dc){

        if(is_link($dc->activeRecord->phpbb_alias) && readlink($dc->activeRecord->phpbb_alias) == $varValue) {
            Message::addInfo("Path to forum already set");
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
                symlink(TL_ROOT . "/vendor/ctsmedia/contao-phpbb-bridge-bundle/src/Resources/phpBB/ctsmedia", $dc->activeRecord->phpbb_alias . '/ext/ctsmedia');
            }

            Message::addInfo("Please activate the contao extension in the phpbb backend");
        } else {
            //Message::addError("Forum could not be found: ".$varValue . "/viewtopic.php");
            throw new Exception("Forum could not be found: ".$varValue . "/viewtopic.php");
        }

        return $varValue;
    }

    public  function updateConfig(DataContainer $dc) {

        // Return if there is no active record (override all)
        if (!$dc->activeRecord || $dc->activeRecord->type != 'phpbb_forum')
        {
            return;
        }

        $row = $dc->activeRecord->row();

        // If it's a new object with no values we can skip here
        if(empty($row['phpbb_path']) && empty($row['phpbb_alias'])){
            return;
        }

        Message::addInfo("Updating Config");
        $row['skipInternalHook'] = true;
        $url = Controller::generateFrontendUrl($row);

        $db = [
            'host' => System::getContainer()->getParameter('database_host'),
            'port' => System::getContainer()->getParameter('database_port'),
            'user' => System::getContainer()->getParameter('database_user'),
            'password' => System::getContainer()->getParameter('database_password'),
            'dbname' => System::getContainer()->getParameter('database_name'),
        ];

        $forumGroups = $dc->activeRecord->phpbb_default_groups;
        if(is_string($dc->activeRecord->phpbb_default_groups)) {
            $forumGroups = unserialize($dc->activeRecord->phpbb_default_groups);
        }

        System::getContainer()->get('phpbb_bridge.connector')->updateConfig(array(
            'contao.forum_pageId' => $dc->activeRecord->id,
            'contao.forum_pageUrl' => Environment::get('url').'/'.$url,
            'contao.url' => Environment::get('url'),
            'contao.forum_pageAlias' => $dc->activeRecord->phpbb_alias,
            'contao.forum_groups' => $forumGroups,
            'contao.bridge_is_installed' => true,
            'contao.db' => $db
        ));
        System::getContainer()->get('phpbb_bridge.connector')->setMandatoryDbConfigValues();
        Message::addInfo('<strong>You may want to clear the forum cache to make sure the new values are active</strong>');
        System::getContainer()->get('phpbb_bridge.connector')->testCookieDomain();
    }


    public function generateForumlayout(DataContainer $dc) {

        // Return if there is no active record (override all)
        if (!$dc->activeRecord || $dc->activeRecord->type != 'phpbb_forum')
        {
            return;
        }

        $row = $dc->activeRecord->row();

        // If it's a new object with no values we can skip here
        if(empty($row['phpbb_path']) && empty($row['phpbb_alias'])){
            return;
        }

        Message::addInfo("Generating Layout");

        $row['skipInternalHook'] = true;
        $url = Controller::generateFrontendUrl($row, null, null, false);

        $frontendRequest = new \Contao\Request();
        $frontendRequest->send(Environment::get('url').'/'.$url);



    }

}