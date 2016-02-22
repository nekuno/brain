<?php

namespace Controller\User;

use Controller\BaseController;
use Model\User;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController extends BaseController
{
    /**
     * @var GroupModel
     */
    protected $gm;

    public function __construct(User $user, GroupModel $gm)
    {
        $this->user = $user;
        $this->gm = $gm;
    }

    public function getAction(Application $app, $id)
    {
        $group = $this->gm->getById($id);

        return $app->json($group);
    }

    public function getMembersAction(Request $request, Application $app, $id)
    {
        $data = $request->query->all();
        $data['userId'] = $this->getUserId();
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

    public function addUserAction(Application $app, $id)
    {
        $this->gm->addUser($id, $this->getUserId());

        return $app->json();
    }

    public function removeUserAction(Application $app, $id)
    {
        $this->gm->removeUser($id, $this->getUserId());

        return $app->json();
    }
}