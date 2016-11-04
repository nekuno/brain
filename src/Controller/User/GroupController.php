<?php

namespace Controller\User;

use Model\User;
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

        $links = $app['links.model']->findPopularLinksByGroup($group->getId());
        $group->setPopularContents($links);

        return $app->json($group);
    }

    public function postAction(Request $request, Application $app, User $user)
    {
        $data = $request->request->all();

        $data['createdBy'] = $user->getId();
        $createdGroup = $app['users.groups.model']->create($data);
        $app['users.groups.model']->addUser($createdGroup->getId(), $user->getId());

        $data['groupId'] = $createdGroup->getId();
        $invitationData = array(
            'userId' => $user->getId(),
            'groupId' => $createdGroup->getId(),
            'available' => 999999999
        );
        $createdInvitation = $app['users.invitations.model']->create($invitationData);

        $createdGroup->setInvitation($createdInvitation);
        return $app->json($createdGroup, 201);
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

        $userManager = $app['users.manager'];
        $profileModel = $app['users.profile.model'];
        $matchingModel = $app['users.matching.model'];

        $usersByGroup = $userManager->getByGroup($id, $data);

        $members = array();
        foreach ($usersByGroup as $userByGroup) {
            $member = User\Recommendation\UserRecommendation::buildFromUser($userByGroup);

            $profile = $profileModel->getById($userByGroup->getId());
            $member->setLocation($profile['location']);
            $member->setMatching($matchingModel->getMatchingBetweenTwoUsers($user->getId(), $userByGroup->getId()));

            $members[] = $member;
        }

        return $app->json($members);

//        foreach ($users as &$user){
//            $user['id'] = $user['qnoow_id'];
//        }
//        /* @var ProfileModel $profileModel */
//        foreach ($users as &$user){
//            $user = array_merge($user, $profileModel->getById($user['qnoow_id']));
//            $user['location'] = $user['location']['locality'].', '.$user['location']['country'];
//        }
//        return $app->json(array('items' => $users));
    }

    public function getContentAction(Request $request, Application $app, $id)
    {
        $contentPaginator = $app['paginator.content'];
        $groupContentModel = $app['users.group.content.model'];
        $filters = array('groupId' => (int)$id);

        $content = $contentPaginator->paginate($filters, $groupContentModel, $request);

        return $app->json($content);
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
        $removed = $app['users.groups.model']->removeUser($id, $user->getId());

        return $app->json($removed, 204);
    }
}