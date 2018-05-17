<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Entity\EmailNotification;
use Model\Invitation\InvitationManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\EmailNotifications;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;
use Symfony\Component\Translation\TranslatorInterface;

class InvitationController extends FOSRestController implements ClassResourceInterface
{
    protected $socialHost;

    public function __construct($socialHost)
    {
        $this->socialHost = $socialHost;
    }

    /**
     * Get paginated invitations
     *
     * @Get("/invitations")
     * @param User $user
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns paginated invitations.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function indexByUserAction(User $user, Request $request, InvitationManager $invitationManager)
    {
        $result = $invitationManager->getPaginatedInvitationsByUser($request->get('offset') ?: 0, $request->get('limit') ?: 20, $user->getId());

        return $this->view($result, 200);
    }

    /**
     * Get invitation
     *
     * @Get("/invitations/{invitationId}", requirements={"invitationId"="\d+"})
     * @param integer $invitationId
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns invitation.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function getAction($invitationId, InvitationManager $invitationManager)
    {
        $result = $invitationManager->getById($invitationId);

        return $this->view($result, 200);
    }

    /**
     * Get available invitations
     *
     * @Get("/invitations/available")
     * @param User $user
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns available invitations count.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function getAvailableByUserAction(User $user, InvitationManager $invitationManager)
    {
        $result = $invitationManager->getUserAvailable($user->getId());

        return $this->view($result, 200);
    }

    /**
     * Set available invitations
     *
     * @Post("/invitations/available/{nOfAvailable}", requirements={"nOfAvailable"="\d+"})
     * @param integer $nOfAvailable
     * @param User $user
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns available invitations count.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function setUserAvailableAction($nOfAvailable, User $user, InvitationManager $invitationManager)
    {
        $invitationManager->setUserAvailable($user->getId(), $nOfAvailable);

        $nOfAvailable = $invitationManager->getUserAvailable($user->getId());

        return $this->view($nOfAvailable, 200);
    }

    /**
     * Create invitation
     *
     * @Post("/invitations")
     * @param User $user
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"available"},
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
     *     description="Returns created invitation.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function postAction(User $user, Request $request, InvitationManager $invitationManager)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        $invitation = $invitationManager->create($data);

        return $this->view($invitation, 201);
    }

    /**
     * Edit invitation
     *
     * @Put("/invitations/{invitationId}", requirements={"invitationId"="\d+"})
     * @param integer $invitationId
     * @param User $user
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"available"},
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
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function putAction($invitationId, User $user, Request $request, InvitationManager $invitationManager)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        $invitation = $invitationManager->update($data + array('invitationId' => $invitationId));

        return $this->view($invitation, 200);
    }

    /**
     * Delete invitation
     *
     * @Delete("/invitations/{invitationId}", requirements={"invitationId"="\d+"})
     * @param integer $invitationId
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted invitation.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function deleteAction($invitationId, InvitationManager $invitationManager)
    {
        $invitation = $invitationManager->getById($invitationId);

        $invitationManager->remove($invitationId);

        return $this->view($invitation, 200);
    }

    /**
     * Validate invitation token
     *
     * @Post("/invitations/token/validate/{token}")
     * @param string $token
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns validated invitation.",
     * )
     * @SWG\Tag(name="invitations")
     */
    public function validateTokenAction($token, InvitationManager $invitationManager)
    {
        $invitation = $invitationManager->validateTokenAvailable($token);

        return $this->view($invitation, 200);
    }

    /**
     * Consume invitation
     *
     * @Post("/invitations/consume/{token}")
     * @param string $token
     * @param User $user
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns consumed invitation.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function consumeAction($token, User $user, InvitationManager $invitationManager)
    {
        $invitation = $invitationManager->consume($token, $user->getId());

        return $this->view($invitation, 200);
    }

    /**
     * Get total invitations count for user
     *
     * @Get("/invitations/count")
     * @param User $user
     * @param InvitationManager $invitationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns total invitations count.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function countByUserAction(User $user, InvitationManager $invitationManager)
    {
        $invitationsCount = $invitationManager->getCountByUserId($user->getId());

        return $this->view($invitationsCount, 200);
    }

    /**
     * Send invitation
     *
     * @Post("/invitations/{invitationId}/send", requirements={"invitationId"="\d+"})
     * @param integer $invitationId
     * @param User $user
     * @param Request $request
     * @param InvitationManager $invitationManager
     * @param TranslatorInterface $translator
     * @param EmailNotifications $emailNotifications
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"email"},
     *          @SWG\Property(property="email", type="string"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns recipients count.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="invitations")
     */
    public function sendAction($invitationId, User $user, Request $request, InvitationManager $invitationManager, TranslatorInterface $translator, EmailNotifications $emailNotifications)
    {
        $data = $request->request->all();
        $sentCount = 0;

        if (isset($data['locale'])) {
            $translator->setLocale($data['locale']);
        }

        if ($sendData = $invitationManager->prepareSend($invitationId, $user->getUsername(), $data, $this->socialHost)) {
            $sentCount = $emailNotifications->send(
                EmailNotification::create()
                    ->setType(EmailNotification::INVITATION)
                    ->setUserId($user->getId())
                    ->setRecipient($data['email'])
                    ->setSubject($translator->trans("notifications.messages.invitation.subject", array('%name%' => $sendData['username'])))
                    ->setInfo($sendData)
            );
        }

        return $this->view($sentCount, 201);
    }
}
