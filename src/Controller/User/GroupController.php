<?php

namespace Controller\User;

use Model\User;
use Model\User\ProfileModel;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController
{
    /**
     * @param Application $app
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAction(Application $app, $id)
    {
        $group = $app['users.groups.model']->getById($id);

        return $app->json($group);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getMembersAction(Request $request, Application $app, User $user, $id)
    {
        $data = $request->query->all();
        $data['userId'] = $user->getId();
        /* @var UserManager $userManager */
        $userManager = $app['users.manager'];
        $usersByGroup = $userManager->getByGroup($id, $data);

        // TODO: Refactor this action, getByGroups returns now objects
        $users = array();
        foreach ($usersByGroup as $u) {
            /* @var $u User */
            $users[] = $u->jsonSerialize();
        }

        foreach ($users as &$user){
            $user['id'] = $user['qnoow_id'];
        }
        /* @var ProfileModel $profileModel */
        $profileModel = $app['users.profile.model'];
        foreach ($users as &$user){
            $user = array_merge($user, $profileModel->getById($user['qnoow_id']));
            $user['location'] = $user['location']['locality'].', '.$user['location']['country'];
        }
        return $app->json(array('items' => $users));
    }

    /**
     * @param Application $app
     * @param User $user
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function addUserAction(Application $app, User $user, $id)
    {
        $app['users.groups.model']->addUser($id, $user->getId());

        return $app->json();
    }

    /**
     * @param Application $app
     * @param User $user
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function removeUserAction(Application $app, User $user, $id)
    {
        $app['users.groups.model']->removeUser($id, $user->getId());

        return $app->json();
    }
}