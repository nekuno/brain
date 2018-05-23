<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Group\GroupManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Service\GroupService;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class GroupController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get all groups
     *
     * @Get("/groups")
     * @param GroupManager $groupManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns all groups.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAllAction(GroupManager $groupManager)
    {
        $groups = $groupManager->getAll();

        return $this->view($groups, 200);
    }

    /**
     * Get group
     *
     * @Get("/groups/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param GroupManager $groupManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns group.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAction($id, GroupManager $groupManager)
    {
        $group = $groupManager->getById($id);

        return $this->view($group, 200);
    }

    /**
     * Create group
     *
     * @Post("/groups")
     * @param Request $request
     * @param GroupService $groupService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\Group\Group::class)
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created group.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function postAction(Request $request, GroupService $groupService)
    {
        $data = $request->request->all();

        $group = $groupService->createGroup($data);

        return $this->view($group, 201);
    }

    /**
     * Edit group
     *
     * @Put("/groups/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param Request $request
     * @param GroupService $groupService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\Group\Group::class)
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns edited group.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function putAction($id, Request $request, GroupService $groupService)
    {
        $data = $request->request->all();

        $group = $groupService->updateGroup($id, $data);

        return $this->view($group, 200);
    }

    /**
     * Delete group
     *
     * @Delete("/groups/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param GroupManager $groupManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted group.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function deleteAction($id, GroupManager $groupManager)
    {
        $group = $groupManager->remove($id);

        return $this->view($group, 200);
    }

    /**
     * Validate group
     *
     * @Post("/groups/validate")
     * @param Request $request
     * @param GroupService $groupService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\Group\Group::class)
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Group is valid.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function validateAction(Request $request, GroupService $groupService)
    {
        $data = $request->request->all();

        if (isset($data['id'])) {
            $groupId = $data['id'];
            unset($data['id']);
            $groupService->validateOnUpdate($data, $groupId);
        } else {
            $groupService->validateOnCreate($data);
        }

        return $this->view([], 200);
    }
}