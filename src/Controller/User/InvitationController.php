<?php

namespace Controller\User;

use Controller\BaseController;
use Model\Entity\EmailNotification;
use Model\User\InvitationModel;
use Service\EmailNotifications;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class InvitationController extends BaseController
{
    public function indexByUserAction(Request $request, Application $app)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getPaginatedInvitationsByUser($request->get('offset') ?: 0, $request->get('limit') ?: 20, $this->getUserId());

        return $app->json($result);
    }

    public function getAction(Application $app, $id)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getById($id);

        return $app->json($result);
    }

    public function getAvailableByUserAction(Application $app)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getUserAvailable($this->getUserId());

        return $app->json($result);
    }

    public function setUserAvailableAction(Application $app, $nOfAvailable)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $model->setUserAvailable($this->getUserId(), $nOfAvailable);

        $nOfAvailable = $model->getUserAvailable($this->getUserId());

        return $nOfAvailable;
    }

    public function postAction(Request $request, Application $app)
    {
        $data = $request->request->all();
        $data['userId'] = $this->getUserId();
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->create($data);

        return $app->json($invitation, 201);
    }

    public function putAction(Request $request, Application $app, $id)
    {
        $data = $request->request->all();
        $data['userId'] = $this->getUserId();
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
        $data['userId'] = $this->getUserId();
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $model->validate($data);

        return $app->json(array(), 200);
    }

    public function consumeAction(Application $app, $token)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->consume($token, $this->getUserId());

        return $app->json($invitation);
    }

    public function countByUserAction(Application $app)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->getCountByUserId($this->getUserId());

        return $app->json($invitation);
    }

    public function sendAction(Request $request, Application $app, $id)
    {
        $data = $request->request->all();
        $recipients = 0;
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        if (isset($data['locale'])) {
            $app['translator']->setLocale($data['locale']);
        }

        if ($sendData = $model->prepareSend($id, $this->getUserId(), $data, $app['social_host'])) {
            /* @var $emailNotification EmailNotifications */
            $emailNotification = $app['emailNotification.service'];
            $recipients = $emailNotification->send(
                EmailNotification::create()
                    ->setType(EmailNotification::INVITATION)
                    ->setUserId($this->getUserId())
                    ->setRecipient($data['email'])
                    ->setSubject($app['translator']->trans('notifications.messages.invitation.subject', array('%name%' => $sendData['username'])))
                    ->setInfo($sendData)
            );
        }

        return $recipients;
    }
}
