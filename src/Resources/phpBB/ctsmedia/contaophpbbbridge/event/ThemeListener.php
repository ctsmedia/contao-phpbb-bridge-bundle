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

namespace ctsmedia\contaophpbbbridge\event;

use ctsmedia\contaophpbbbridge\contao\Connector;
use phpbb\event\data;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Listener for phpbb template / layout events. Mostly for Layout sharing
 *
 * @package ctsmedia\contaophpbbbridge\event
 * @author Daniel Schwiperich <https://github.com/DanielSchwiperich>
 */
class ThemeListener implements EventSubscriberInterface
{

    /**
     * @var \phpbb\template\template
     */
    protected $template;

    protected $contaoConnector;

    protected $config = array();

    /**
     * ThemeListener constructor.
     * @param $phpbb_root_path
     * @param \phpbb\template\template $template
     */
    public function __construct($phpbb_root_path , \phpbb\template\twig\twig $template, Connector $contaoConnector, array $config)
    {
        $this->template = $template;
        $this->contaoConnector = $contaoConnector;
        $this->config = $config;

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'core.user_setup'	=> 'loadLanguageSetup',
            'core.page_header' => 'initContaoLayout',
            'core.page_footer' => 'postProcessLayout'
        );
    }

    /**
     * Load and set Layout Sections during
     * early phpbb page / template processing
     *
     * @param Event $event
     */
    public function initContaoLayout(data $event) {

        // Load dynamic rendered layout sections
        // and syncs session for loggedin users
        $sections = $this->contaoConnector->loadLayout();

        if(isset($sections->overall_header)) {
            $this->template->assign_var('CONTAO_LAYOUT_HEADER', $sections->overall_header);
        }
        if(isset($sections->overall_footer)) {
            $this->template->assign_var('CONTAO_LAYOUT_FOOTER', $sections->overall_footer);
        }

    }

    /**
     * Do some postprocessing just before the page gets rendered
     *
     * @param Event $event
     */
    public function postProcessLayout(Event $event) {

        if(isset($this->config['body_class'])) {
            $this->template->append_var('BODY_CLASS', $this->config['body_class']);
        }

        //$this->contaoConnector->test();

    }

    /**
     * Load language Files
     */
    public function loadLanguageSetup($event) {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'ctsmedia/contaophpbbbridge',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }


}