<?php
/*
 * This file is part of contao-phpbb-bridge-bundle
 * 
 * Copyright (c) CTS GmbH
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 */

namespace Ctsmedia\Phpbb\BridgeBundle\EventListener;


use Contao\FrontendUser;
use Contao\ModulePersonalData;
use Contao\User;
use Ctsmedia\Phpbb\BridgeBundle\PhpBB\Connector;
use Monolog\Logger;


/**
 *
 * @package Ctsmedia\Phpbb\BridgeBundle\EventListener
 * @author Daniel Schwiperich <d.schwiperich@cts-media.eu>
 */
class ContaoMemberListener
{
    /**
     * @var Connector
     */
    private $phpBBConnector;

    /**
     * @var Logger
     */
    private $logger;


    /**
     * ContaoMemberListener constructor.
     * @param Connector $phpBBConnector
     * @param Logger $logger
     */
    public function __construct(Connector $phpBBConnector, Logger $logger)
    {

        $this->phpBBConnector = $phpBBConnector;
        $this->logger = $logger;
    }


    /**
     * Updates the phpbb user if the contao member profile changes. Currently only email is supported
     * since this is the only field in both systems
     *
     * username is not changeable.
     * password @see onSetNewPassword
     *
     * Contao Hook: updatePersonalData
     *
     * @param User $user
     * @param array $data
     * @param ModulePersonalData $modulePersonalData
     */
    public function onUpdatePersonalData(User $user, array $data, ModulePersonalData $modulePersonalData)
    {
        // We are only interested in Contao Members
        if (!$user instanceof FrontendUser) {
            return;
        }

        if (isset($data['email'])) {
            $phpBBUser = $this->phpBBConnector->getUser($user->username);
            // If a correspondenting phpBB User was found sync  emails
            if ($phpBBUser !== null) {
                $success = $this->phpBBConnector->updateUser($phpBBUser->user_id, ['user_email' => $data['email']]);
                if ($success) {
                    $this->logger->info("Member {$user->username} email was synced to his phpbb profile");
                }
            }
        }


    }

    /**
     * Invalidate phpBB user password if contao member changed his one.
     * phpBB will then ask contao on next login if given password matches and update the phpbb user password accordingly
     *
     * Contao Hook: setNewPassword
     *
     * @param $user
     * @param $strPassword
     */
    public function onSetNewPassword($user, $strPassword)
    {
        $phpBBUser = $this->phpBBConnector->getUser($user->username);
        // If a correspondenting phpBB User was found sync  emails
        if ($phpBBUser !== null) {
            $success = $this->phpBBConnector->updateUser($phpBBUser->user_id, ['user_password' => Connector::IMPORT_USER_PASSWORD_PREFIX . $strPassword]);
            if ($success) {
                $this->logger->info("Member {$user->username} updated his password. phpbb password was invalidated therefore.");
            }
        }
    }

}