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

        $this->Template->main = "%%FORUM%%";

        $response = $this->Template->getResponse($blnCheckRequest);

        return $response;
    }

}