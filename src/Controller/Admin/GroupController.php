<?php

namespace Controller\Admin;

use Model\User\GroupModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController
{

    /**
     * @var GroupModel
     */
    protected $gm;

    public function __construct(GroupModel $gm)
    {
        $this->gm = $gm;
    }

    public function getAllAction(Application $app)
    {

        $groups = $this->gm->getAll();

        return $app->json($groups);

    }

    public function postAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        $group = $this->gm->create($data);

        return $app->json($group, 201);
    }

    public function putAction(Request $request, Application $app, $id)
    {

        $data = $request->request->all();

        $group = $this->gm->update($id, $data);

        return $app->json($group);
    }

    public function deleteAction(Application $app, $id)
    {

        $group = $this->gm->remove($id);

        return $app->json($group);
    }

    public function validateAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        $this->gm->validate($data);

        return $app->json();
    }
}