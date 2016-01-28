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

namespace Ctsmedia\Phpbb\BridgeBundle\EventListener;

use Contao\CoreBundle\EventListener\ScopeAwareTrait;
use Contao\CoreBundle\EventListener\UserAwareTrait;
use Contao\FrontendUser;
use Contao\System;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 *
 * @package EventListener
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class SyncSessionListener
{
    use UserAwareTrait;
    use ScopeAwareTrait;

    /**
     * Syncs the session (refreshs) to the forum
     *
     * @param User $user
     */
    public function onSyncForumSession(FilterResponseEvent $event) {

        if (!$this->hasUser() || !$this->isFrontendMasterRequest($event)) {
            return;
        }

        $user = $this->getUserObject();

        if (!$user instanceof FrontendUser) {
            return;
        }

        // Only sync for frontend users and if it's not alreay a internal request
        if(!$event->getRequest()->attributes->get('isInternalForumRequest', false)) {
            $result = System::getContainer()->get('phpbb_bridge.connector')->syncForumSession();
            if($result === false) {
                System::log($user->username . ' is loggend in to contao but not to the forum (anymore)', __METHOD__, TL_ERROR);
            }
        }
    }

    /**
     * Returns the user object depending on the container scope.
     *
     * @return FrontendUser|BackendUser|null The user object
     */
    private function getUserObject()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

}