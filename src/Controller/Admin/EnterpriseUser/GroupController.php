<?php

namespace Controller\Admin\EnterpriseUser;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\EnterpriseUser\EnterpriseUserManager;
use Model\Group\GroupManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Service\GroupService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class GroupController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get all groups for enterprise user
     *
     * @Get("/enterpriseUsers/{enterpriseUserId}/groups", requirements={"enterpriseUserId"="\d+"})
     * @param integer $enterpriseUserId
     * @param GroupManager $groupManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns groups.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAllAction($enterpriseUserId, GroupManager $groupManager)
    {
        $groups = $groupManager->getAllByEnterpriseUserId($enterpriseUserId);

        return $this->view($groups, 200);
    }

    /**
     * Get group for enterprise user
     *
     * @Get("/enterpriseUsers/{enterpriseUserId}/groups/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $id
     * @param integer $enterpriseUserId
     * @param GroupManager $groupManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns group.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAction($id, $enterpriseUserId, GroupManager $groupManager)
    {
        $group = $groupManager->getByIdAndEnterpriseUserId($id, $enterpriseUserId);

        return $this->view($group, 200);
    }

    /**
     * Create enterprise user group
     *
     * @Post("/enterpriseUsers/{enterpriseUserId}/groups", requirements={"enterpriseUserId"="\d+"})
     * @param integer $enterpriseUserId
     * @param Request $request
     * @param GroupManager $groupManager
     * @param GroupService $groupService
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=201,
     *     description="Returns created enterprise user group.",
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\Group\Group::class)
     *      )
     * )
     * @SWG\Tag(name="admin")
     */
    public function postAction($enterpriseUserId, Request $request, GroupManager $groupManager, GroupService $groupService, EnterpriseUserManager $enterpriseUserManager)
    {
        $data = $request->request->all();

        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $groupService->createGroup($data);
        $groupManager->setCreatedByEnterpriseUser($group->getId(), $enterpriseUserId);

        return $this->view($group, 201);
    }

    /**
     * Edit enterprise user group
     *
     * @Put("/enterpriseUsers/{enterpriseUserId}/groups/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $id
     * @param integer $enterpriseUserId
     * @param Request $request
     * @param GroupService $groupService
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=201,
     *     description="Returns edited enterprise user group.",
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\Group\Group::class)
     *      )
     * )
     * @SWG\Tag(name="admin")
     */
    public function putAction($id, $enterpriseUserId, Request $request, GroupService $groupService, EnterpriseUserManager $enterpriseUserManager)
    {
        $data = $request->request->all();

        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $groupService->updateGroup($id, $data);

        return $this->view($group, 200);
    }

    /**
     * Delete enterprise user group
     *
     * @Delete("/enterpriseUsers/{enterpriseUserId}/groups/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $id
     * @param integer $enterpriseUserId
     * @param GroupManager $groupManager
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted enterprise user group.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function deleteAction($id, $enterpriseUserId, GroupManager $groupManager, EnterpriseUserManager $enterpriseUserManager)
    {
        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $groupManager->remove($id);

        return $this->view($group, 200);
    }

    /**
     * Validate enterprise user group
     *
     * @Post("/enterpriseUsers/{enterpriseUserId}/groups/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $enterpriseUserId
     * @param Request $request
     * @param GroupService $groupService
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Successful validation.",
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\Group\Group::class)
     *      )
     * )
     * @SWG\Tag(name="admin")
     */
    public function validateAction($enterpriseUserId, Request $request, GroupService $groupService, EnterpriseUserManager $enterpriseUserManager)
    {
        $data = $request->request->all();

        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        if (isset($data['id'])) {
            $groupId = $data['id'];
            unlink($data['id']);
            $groupService->validateOnUpdate($data, $groupId);
        } else {
            $groupService->validateOnCreate($data);
        }

        return $this->view([], 200);
    }
}