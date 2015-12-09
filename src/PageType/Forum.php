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
     * @param \PageModel $objPage
     * @param boolean    $blnCheckRequest
     *
     * @return Response
     */
    public function getResponse($objPage, $blnCheckRequest=false)
    {
        $this->prepare($objPage);

        $forumRequest = new Request();

       // $this->Template->dumpTemplateVars();
        $this->Template->main = "xxx";

        $response = $this->Template->getResponse($blnCheckRequest);

        dump($this->getContainer()->getParameter('phpbb_bridge.phpbb_dir'));
        dump($this->getContainer()->getParameter('kernel.root_dir'));

        $currentDir = getcwd();
        chdir($this->getContainer()->getParameter('kernel.root_dir') . '/../web/' . $this->getContainer()->getParameter('phpbb_bridge.phpbb_dir'));

        include_once  'index.php';

        chdir($currentDir);

        return $response;
    }

}