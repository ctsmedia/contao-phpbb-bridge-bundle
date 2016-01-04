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

namespace Ctsmedia\Phpbb\BridgeBundle\PageType;

use Contao\PageRegular;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\PageType
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class Forum extends PageRegular
{

    /**
     * Return a response object
     *
     * The forum Page usually does not get called because the frontend listener
     * overrides the url for navigation for example. When called directly the layout parts
     * phpBB gets created und pushed to phpbb
     *
     * @see ContaoFrontendListener
     *
     * @param \PageModel $objPage
     * @param boolean    $blnCheckRequest
     *
     * @return Response
     */
    public function getResponse($objPage, $blnCheckRequest=false)
    {
        $this->prepare($objPage);

        //dump($this->Template);

        $this->Template->main = "%%FORUM%%";

        $style = $this->Template->replaceInsertTags($this->Template->stylesheets);
        $style = $this->Template->replaceDynamicScriptTags($style);
        $style = preg_replace('/href\=\"(?!http|\/)/', 'href="/', $style);

        $head = $this->Template->replaceInsertTags($this->Template->head);
        $head = $this->Template->replaceDynamicScriptTags($head);
        $head = preg_replace('/src\=\"(?!http|\/)/', 'src="/', $head);
        $head = preg_replace('/href\=\"(?!http|\/)/', 'href="/', $head);
        $this->Template->head = "";


        // @todo Add framework, mooscripts etc?
        $phpbbHeaders = "";
        $phpbbHeaders .= $style;
        $phpbbHeaders .= $head;

        file_put_contents(__DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/styles/all/template/event/overall_header_stylesheets_after.html', $phpbbHeaders);

        //dump($phpbbHeaders);

        //dump($this->Template->class);


        $response = $this->Template->getResponse($blnCheckRequest);
        $html = $response->getContent();

        // Ajust link paths
        $html = preg_replace('/href\=\"(?!http|\/)/', 'href="/', $html);

        // Ajust src paths
        $html = preg_replace('/src\=\"(?!http|\/)/', 'src="/', $html);
        $html = preg_replace('/content\=\"(?!http|\/)/', 'content="/', $html);

        $parts = explode("%%FORUM%%", $html);
        $overall_header = $parts[0];
        $overall_footer = $parts[1];


        $overall_header = preg_replace('/<\!DOC.*/i', '', $overall_header);
        $overall_header = preg_replace('/<html.*/i', '', $overall_header);
        $overall_header = preg_replace('/<body.*/i', '', $overall_header);
        $overall_header = preg_replace('/<head>.*<\/head>/s', '', $overall_header);

        $overall_footer = preg_replace('/<\/body.*/i', '', $overall_footer);
        $overall_footer = preg_replace('/<\/html.*/i', '', $overall_footer);

        //dump($overall_footer);
        //dump($this->Template);

        //dump($html);


        file_put_contents(__DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/styles/all/template/event/overall_header_body_before.html', $overall_header);
        file_put_contents(__DIR__ . '/../Resources/phpBB/ctsmedia/contaophpbbbridge/styles/all/template/event/overall_footer_after.html', $overall_footer);

        System::getContainer()->get('phpbb_bridge.connector')->updateConfig(array('contao.body_class' => $this->Template->class));

        return $response;
    }

}