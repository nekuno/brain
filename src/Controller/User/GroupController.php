<?php

namespace Controller\User;

use Model\User\GroupModel;
use Model\User\ProfileModel;
use Model\UserModel;
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

    public function getAction(Application $app, $id)
    {

        $group = $this->gm->getById($id);

        return $app->json($group);
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

    public function getMembersAction(Request $request, Application $app, $id)
    {
        $data = $request->query->all();
        /** @var UserModel $userModel */
        $userModel = $app['users.model'];
        $users = $userModel->getByGroup($id, $data);

        foreach ($users as &$user){
            $user['id'] = $user['qnoow_id'];
        }
        /** @var ProfileModel $profileModel */
        $profileModel = $app['users.profile.model'];
        foreach ($users as &$user){
            $user = array_merge($user, $profileModel->getById($user['qnoow_id']));
            $user['location'] = $user['location']['locality'].', '.$user['location']['country'];
        }
        return $app->json(array('items' => $users));
    }

    public function addUserAction(Application $app, $id, $userId)
    {

        $this->gm->addUser($id, $userId);

        return $app->json();
    }

    public function removeUserAction(Application $app, $id, $userId)
    {

        $this->gm->removeUser($id, $userId);

        return $app->json();
    }

}