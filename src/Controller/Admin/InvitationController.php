<?php

namespace Controller\Admin;

use Model\User\InvitationModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class InvitationController
{
    public function indexAction(Request $request, Application $app)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getPaginatedInvitations($request->get('offset') ?: 0, $request->get('limit') ?: 20);

        return $app->json($result);
    }
}