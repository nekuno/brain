<?php

namespace Controller\User;

use Model\Entity\EmailNotification;
use Model\User\InvitationModel;
use Service\EmailNotifications;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class InvitationController
{

    public function indexByUserAction(Request $request, Application $app)
    {
        // TODO: Change with $this->getUserId()
        $id = $request->request->get('userId');

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

    public function getAvailableByUserAction(Request $request, Application $app)
    {
        // TODO: Change with $this->getUserId() and remove Request from parameters
        $id = $request->request->get('userId');

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getUserAvailable($id);

        return $app->json($result);
    }

    public function setUserAvailableAction(Request $request, Application $app, $nOfAvailable)
    {
        // TODO: Change with $this->getUserId() and remove Request from parameters
        $id = $request->request->get('userId');

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

    public function consumeAction(Request $request, Application $app, $token)
    {
        // TODO: Change with $this->getUserId() and remove Request from parameters
        $userId = $request->request->get('userId');

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->consume($token, $userId);

        return $app->json($invitation);
    }

    public function countByUserAction(Request $request, Application $app)
    {
        // TODO: Change with $this->getUserId() and remove Request from parameters
        $id = $request->request->get('userId');

        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->getCountByUserId($id);

        return $app->json($invitation);
    }

    public function sendAction(Request $request, Application $app, $id)
    {
        // TODO: Change with $this->getUserId()
        $userId = $request->request->get('userId');

        $data = $request->request->all();
        $recipients = 0;
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        if (isset($data['locale'])) {
            $app['translator']->setLocale($data['locale']);
        }

        if ($sendData = $model->prepareSend($id, $userId, $data, $app['social_host'])) {
            /* @var $emailNotification EmailNotifications */
            $emailNotification = $app['emailNotification.service'];
            $recipients = $emailNotification->send(
                EmailNotification::create()
                    ->setType(EmailNotification::INVITATION)
                    ->setUserId($userId)
                    ->setRecipient($data['email'])
                    ->setSubject($app['translator']->trans('notifications.messages.invitation.subject', array('%name%' => $sendData['username'])))
                    ->setInfo($sendData)
            );
        }

        return $recipients;
    }
}
