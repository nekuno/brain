<?php

namespace Controller\User;

use Model\Entity\EmailNotification;
use Model\User\InvitationModel;
use Model\User;
use Service\EmailNotifications;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class InvitationController
{
    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function indexByUserAction(Request $request, Application $app, User $user)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getPaginatedInvitationsByUser($request->get('offset') ?: 0, $request->get('limit') ?: 20, $user->getId());

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAction(Application $app, $id)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getById($id);

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAvailableByUserAction(Application $app, User $user)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $result = $model->getUserAvailable($user->getId());

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param User $user
     * @param integer $nOfAvailable
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function setUserAvailableAction(Application $app, User $user, $nOfAvailable)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $model->setUserAvailable($user->getId(), $nOfAvailable);

        $nOfAvailable = $model->getUserAvailable($user->getId());

        return $nOfAvailable;
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function postAction(Request $request, Application $app, User $user)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->create($data);

        return $app->json($invitation, 201);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function putAction(Request $request, Application $app, User $user, $id)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->update($data + array('invitationId' => $id));

        return $app->json($invitation);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Application $app, $id)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->getById($id);

        $model->remove($id);

        return $app->json($invitation);
    }

    /**
     * @param Application $app
     * @param string $token
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function validateTokenAction(Application $app, $token)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->validateToken($token);

        return $app->json($invitation, 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function validateAction(Request $request, Application $app, User $user)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $model->validate($data);

        return $app->json(array(), 200);
    }

    /**
     * @param Application $app
     * @param User $user
     * @param string $token
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function consumeAction(Application $app, User $user, $token)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        $invitation = $model->consume($token, $user->getId());

        return $app->json($invitation);
    }

    /**
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function countByUserAction(Application $app, User $user)
    {
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];
        $invitation = $model->getCountByUserId($user->getId());

        return $app->json($invitation);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function sendAction(Request $request, Application $app, User $user, $id)
    {
        $data = $request->request->all();
        $recipients = 0;
        /* @var $model InvitationModel */
        $model = $app['users.invitations.model'];

        if (isset($data['locale'])) {
            $app['translator']->setLocale($data['locale']);
        }

        if ($sendData = $model->prepareSend($id, $user->getId(), $data, $app['social_host'])) {
            /* @var $emailNotification EmailNotifications */
            $emailNotification = $app['emailNotification.service'];
            $recipients = $emailNotification->send(
                EmailNotification::create()
                    ->setType(EmailNotification::INVITATION)
                    ->setUserId($user->getId())
                    ->setRecipient($data['email'])
                    ->setSubject($app['translator']->trans('notifications.messages.invitation.subject', array('%name%' => $sendData['username'])))
                    ->setInfo($sendData)
            );
        }

        return $recipients;
    }
}
