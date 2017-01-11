<?php

namespace Controller\Admin\EnterpriseUser;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EnterpriseUserController
 * @package Controller
 */
class EnterpriseUserController
{
    public function getAction(Application $app, $id)
    {

        $enterpriseUser = $app['enterpriseUsers.model']->getById($id);

        return $app->json($enterpriseUser);
    }

    public function postAction(Request $request, Application $app)
    {
        $data = $request->request->all();

        $enterpriseUser = $app['enterpriseUsers.model']->create($data);

        return $app->json($enterpriseUser, 201);
    }

    public function putAction(Request $request, Application $app, $id)
    {
        $data = $request->request->all();

        $enterpriseUser = $app['enterpriseUsers.model']->update($id, $data);

        return $app->json($enterpriseUser, 200);
    }

    public function deleteAction(Application $app, $id)
    {
        $enterpriseUser = $app['enterpriseUsers.model']->getById($id);
        $app['enterpriseUsers.model']->remove($id);

        return $app->json($enterpriseUser);
    }

    public function validateAction(Request $request, Application $app)
    {
        $data = $request->request->all();

        $app['enterpriseUsers.model']->validate($data);

        return $app->json();
    }
}