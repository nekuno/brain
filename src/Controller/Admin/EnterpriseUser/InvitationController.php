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
use Model\Invitation\InvitationManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class InvitationController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get enterprise user invitations
     *
     * @Get("/enterpriseUsers/{enterpriseUserId}/invitations/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $id
     * @param integer $enterpriseUserId
     * @param EnterpriseUserManager $enterpriseUserManager
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns enterprise user invitations.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAction($id, $enterpriseUserId, EnterpriseUserManager $enterpriseUserManager, InvitationManager $invitationManager)
    {
        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $invitationManager->getById($id);

        return $this->view($invitation, 200);
    }

    /**
     * Create enterprise user invitation
     *
     * @Post("/enterpriseUsers/{enterpriseUserId}/invitations", requirements={"enterpriseUserId"="\d+"})
     * @param integer $enterpriseUserId
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=201,
     *     description="Returns created enterprise user invitation.",
     * )
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
     * @SWG\Tag(name="admin")
     */
    public function postAction($enterpriseUserId, Request $request, EnterpriseUserManager $enterpriseUserManager, InvitationManager $invitationManager)
    {
        $data = $request->request->all();

        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $invitationManager->create($data);

        return $this->view($invitation, 201);
    }

    /**
     * Edit enterprise user invitation
     *
     * @Put("/enterpriseUsers/{enterpriseUserId}/invitations/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $id
     * @param integer $enterpriseUserId
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=201,
     *     description="Returns edited enterprise user invitation.",
     * )
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
     * @SWG\Tag(name="admin")
     */
    public function putAction($id, $enterpriseUserId, Request $request, EnterpriseUserManager $enterpriseUserManager, InvitationManager $invitationManager)
    {
        $data = $request->request->all();

        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $invitationManager->update($data + array('invitationId' => $id));

        return $this->view($invitation, 200);
    }

    /**
     * Delete enterprise user invitation
     *
     * @Delete("/enterpriseUsers/{enterpriseUserId}/invitations/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $id
     * @param integer $enterpriseUserId
     * @param InvitationManager $invitationManager
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted enterprise user invitation.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function deleteAction($id, $enterpriseUserId, EnterpriseUserManager $enterpriseUserManager, InvitationManager $invitationManager)
    {
        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $invitationManager->getById($id);
        $invitationManager->remove($id);

        return $this->view($invitation, 200);
    }

    /**
     * Validate enterprise user invitation
     *
     * @Post("/enterpriseUsers/{enterpriseUserId}/invitations/{id}", requirements={"enterpriseUserId"="\d+", "id"="\d+"})
     * @param integer $enterpriseUserId
     * @param Request $request
     * @param InvitationManager $invitationManager
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
     *          required={"token", "available"},
     *          @SWG\Property(property="invitationId", type="integer"),
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
     * @SWG\Tag(name="admin")
     */
    public function validateAction($enterpriseUserId, Request $request, EnterpriseUserManager $enterpriseUserManager, InvitationManager $invitationManager)
    {
        $data = $request->request->all();

        if(!$enterpriseUserManager->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        if (isset($data['invitationId'])) {
            $invitationManager->validateUpdate($data);
        } else {
            $invitationManager->validateCreate($data);
        }

        return $this->view([], 200);
    }

}