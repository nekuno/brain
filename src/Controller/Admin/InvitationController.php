<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Invitation\InvitationManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class InvitationController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get paginated invitations
     *
     * @Get("/invitations")
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0,
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20,
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns invitations.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function indexAction(Request $request, InvitationManager $invitationManager)
    {
        $result = $invitationManager->getPaginatedInvitations($request->get('offset') ?: 0, $request->get('limit') ?: 20);

        return $this->view($result, 200);
    }

    /**
     * Get invitation
     *
     * @Get("/invitations/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns invitation.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAction($id, InvitationManager $invitationManager)
    {
        $result = $invitationManager->getById($id);

        return $this->view($result, 200);
    }

    /**
     * Create invitation
     *
     * @Post("/invitations")
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"token", "available"},
     *          @SWG\Property(property="token", type="string"),
     *          @SWG\Property(property="available", type="integer"),
     *          @SWG\Property(property="consumed", type="integer"),
     *          @SWG\Property(property="email", type="string"),
     *          @SWG\Property(property="expiresAt", type="integer"),
     *          @SWG\Property(property="createdAt", type="integer"),
     *          @SWG\Property(property="userId", type="integer"),
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="htmlText", type="string"),
     *          @SWG\Property(property="slogan", type="string"),
     *          @SWG\Property(property="image_url", type="string"),
     *          @SWG\Property(property="image_path", type="string"),
     *          @SWG\Property(property="orientationRequired", type="boolean"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created invitation.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function postAction(Request $request, InvitationManager $invitationManager)
    {
        $data = $request->request->all();
        $invitation = $invitationManager->create($data);

        return $this->view($invitation, 201);
    }

    /**
     * Edit invitation
     *
     * @Put("/invitations/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"token", "available"},
     *          @SWG\Property(property="token", type="string"),
     *          @SWG\Property(property="available", type="integer"),
     *          @SWG\Property(property="consumed", type="integer"),
     *          @SWG\Property(property="email", type="string"),
     *          @SWG\Property(property="expiresAt", type="integer"),
     *          @SWG\Property(property="createdAt", type="integer"),
     *          @SWG\Property(property="userId", type="integer"),
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="htmlText", type="string"),
     *          @SWG\Property(property="slogan", type="string"),
     *          @SWG\Property(property="image_url", type="string"),
     *          @SWG\Property(property="image_path", type="string"),
     *          @SWG\Property(property="orientationRequired", type="boolean"),
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns edited invitation.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function putAction($id, Request $request, InvitationManager $invitationManager)
    {
        $data = $request->request->all();
        $invitation = $invitationManager->update($data + array('invitationId' => $id));

        return $this->view($invitation, 200);
    }

    /**
     * Delete invitation
     *
     * @Delete("/invitations/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted invitation.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function deleteAction($id, InvitationManager $invitationManager)
    {
        $invitation = $invitationManager->getById($id);
        $invitationManager->remove($id);

        return $this->view($invitation, 200);
    }

    /**
     * Validate invitation
     *
     * @Post("/invitations/validate")
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"token", "available"},
     *          @SWG\Property(property="token", type="string"),
     *          @SWG\Property(property="available", type="integer"),
     *          @SWG\Property(property="consumed", type="integer"),
     *          @SWG\Property(property="email", type="string"),
     *          @SWG\Property(property="expiresAt", type="integer"),
     *          @SWG\Property(property="createdAt", type="integer"),
     *          @SWG\Property(property="userId", type="integer"),
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="htmlText", type="string"),
     *          @SWG\Property(property="slogan", type="string"),
     *          @SWG\Property(property="image_url", type="string"),
     *          @SWG\Property(property="image_path", type="string"),
     *          @SWG\Property(property="orientationRequired", type="boolean"),
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Invitation is valid.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function validateAction(Request $request, InvitationManager $invitationManager)
    {
        $data = $request->request->all();

        if (isset($data['invitationId'])) {
            $invitationManager->validateUpdate($data);
        } else {
            $invitationManager->validateCreate($data);
        }

        return $this->view([], 200);
    }

}