<?php

namespace Controller\Social;

use Model\Entity\EmailNotification;
use Model\User\InvitationModel;
use Model\User;
use Service\EmailNotifications;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class InvitationController
{
    /**
     * @param Application $app
     * @param integer $id
     * @param string $token
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function consumeAction(Application $app, $id, $token)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->consume($token, $id);

        return $app->json($invitation);
    }
}
