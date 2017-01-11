<?php

namespace Controller\Admin\EnterpriseUser;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController
{
    public function getAllAction(Application $app, $enterpriseUserId)
    {
        $groups = $app['users.groups.model']->getAllByEnterpriseUserId($enterpriseUserId);

        return $app->json($groups);
    }

    public function getAction(Application $app, $id, $enterpriseUserId)
    {
        $group = $app['users.groups.model']->getByIdAndEnterpriseUserId($id, $enterpriseUserId);

        return $app->json($group);
    }

    public function postAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $app['users.groups.model']->create($data);
        $app['users.groups.model']->setCreatedByEnterpriseUser($group->getId(), $enterpriseUserId);

        return $app->json($group, 201);
    }

    public function putAction(Request $request, Application $app, $id, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $app['users.groups.model']->update($id, $data);

        return $app->json($group);
    }

    public function deleteAction(Application $app, $id, $enterpriseUserId)
    {
        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $app['users.groups.model']->remove($id);

        return $app->json($group);
    }

    public function validateAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$app['enterpriseUsers.model']->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $app['users.groups.model']->validate($data);

        return $app->json();
    }
}