<?php

namespace Controller\Admin\EnterpriseUser;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class InvitationController
 * @package Controller
 */
class InvitationController
{
    public function getAction(Application $app, $id, $enterpriseUserId)
    {
        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $app['users.invitations.model']->getById($id);

        return $app->json($invitation);
    }

    public function postAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $app['users.invitations.model']->create($data);

        return $app->json($invitation, 201);
    }

    public function putAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $app['users.invitations.model']->update($data);

        return $app->json($invitation, 201);
    }

    public function deleteAction(Application $app, $id, $enterpriseUserId)
    {
        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $app['users.invitations.model']->getById($id);
        $app['users.invitations.model']->remove($id);

        return $app->json($invitation);
    }

    public function validateAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $app['users.invitations.model']->validate($data);

        return $app->json();
    }

}