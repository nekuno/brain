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

    public function getAction(Application $app, $id)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getById($id);

        return $app->json($result);
    }

    public function postAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->create($data);

        return $app->json($invitation, 201);
    }

    public function putAction(Request $request, Application $app, $id)
    {

        $data = $request->request->all();

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->update($data + array('invitationId' => $id));

        return $app->json($invitation);
    }

    public function deleteAction(Application $app, $id)
    {

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->getById($id);

        $model->remove($id);

        return $app->json($invitation);
    }

    public function validateAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $model->validate($data, false);

        return $app->json(array(), 200);
    }

}