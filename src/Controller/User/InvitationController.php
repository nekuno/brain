<?php

namespace Controller\User;

use Model\Entity\EmailNotification;
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

    public function indexByUserAction(Request $request, Application $app, $id)
    {

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getPaginatedInvitationsByUser($request->get('offset') ?: 0, $request->get('limit') ?: 20, $id);

        return $app->json($result);
    }

    public function getAction(Application $app, $id)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getById($id);

        return $app->json($result);
    }

    public function getAvailableByUserAction(Application $app, $id)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getUserAvailable($id);

        return $app->json($result);
    }

    public function setUserAvailableAction(Application $app, $id, $nOfAvailable)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $model->setUserAvailable($id, $nOfAvailable);

        $nOfAvailable = $model->getUserAvailable($id);

        return $nOfAvailable;
    }

    public function postAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        /** @var  $TokenGenerator \Service\TokenGenerator */
        $tokenGenerator = $app['tokenGenerator.service'];

        $invitation = $model->create($data, $tokenGenerator);

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

    public function deleteAllAction(Application $app)
    {

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $model->removeAll();

        return true;
    }

    public function validateTokenAction(Application $app, $token)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->validateToken($token);

        return $app->json($invitation, 200);
    }

    public function validateAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $model->validate($data, false);

        return $app->json(array(), 200);
    }

    public function consumeAction(Application $app, $token, $userId)
    {

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->consume($token, $userId);

        return $app->json($invitation);
    }

    public function countTotalAction(Application $app)
    {

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->getCountTotal();

        return $app->json($invitation);
    }

    public function countByUserAction(Application $app, $id)
    {

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->getCountByUserId($id);

        return $app->json($invitation);
    }

    public function sendAction(Request $request, Application $app, $id, $userId)
    {
        $data = $request->request->all();
        $recipients = 0;
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        if($sendData = $model->prepareSend($id, $userId, $data))
        {
            $recipients = $app['emailNotification.service']->send(EmailNotification::create()
                ->setType(EmailNotification::INVITATION)
                ->setUserId($userId)
                ->setRecipient($data['email'])
                ->setSubject($app['translator']->trans('notifications.messages.invitation.subject', array('%name%' => $sendData['username'] )))
                ->setInfo($sendData));
        }
        return $recipients;
    }
}
