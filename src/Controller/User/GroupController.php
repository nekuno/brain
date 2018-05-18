<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Invitation\InvitationManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\GroupService;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class GroupController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Create group
     *
     * @Post("/groups")
     * @param User $user
     * @param Request $request
     * @param GroupService $groupService
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"name"},
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="html", type="string"),
     *          @SWG\Property(property="date", type="integer"),
     *          @SWG\Property(property="image_path", type="string"),
     *          @SWG\Property(property="location", type="object"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created group.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="groups")
     */
    public function postAction(User $user, Request $request, GroupService $groupService, InvitationManager $invitationManager)
    {
        $data = $request->request->all();

        $data['createdBy'] = $user->getId();
        $createdGroup = $groupService->createGroup($data);
        $groupService->addUser($createdGroup->getId(), $user->getId());

        $data['groupId'] = $createdGroup->getId();
        $invitationData = array(
            'userId' => $user->getId(),
            'groupId' => $createdGroup->getId(),
            'available' => 999999999
        );
        $createdInvitation = $invitationManager->create($invitationData);

        $createdGroup->setInvitation($createdInvitation);

        return $this->view($createdGroup, 201);
    }

    /**
     * Add user to group
     *
     * @Post("/groups/{groupId}/members")
     * @param integer $groupId
     * @param User $user
     * @param GroupService $groupService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns group where user has been added.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="groups")
     */
    public function addUserAction($groupId, User $user, GroupService $groupService)
    {
        $group = $groupService->addUser((int)$groupId, $user->getId());

        return $this->view($group, 200);
    }

    /**
     * Remove user from group
     *
     * @Delete("/groups/{groupId}/members")
     * @param integer $groupId
     * @param User $user
     * @param GroupService $groupService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns true if user has been removed from group.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="groups")
     */
    public function removeUserAction($groupId, User $user, GroupService $groupService)
    {
        $removed = $groupService->removeUser((int)$groupId, $user->getId());

        return $this->view($removed, 200);
    }
}