<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Privacy\PrivacyManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * Class PrivacyController
 * @package Controller
 */
class PrivacyController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get own privacy
     *
     * @Get("/privacy")
     * @param User $user
     * @param PrivacyManager $privacyManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns own privacy",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="privacies")
     */
    public function getAction(User $user, PrivacyManager $privacyManager)
    {
        $privacy = $privacyManager->getById($user->getId());

        return $this->view($privacy);
    }

    /**
     * Create privacy
     *
     * @Post("/privacy")
     * @param User $user
     * @param Request $request
     * @param PrivacyManager $privacyManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns created privacy",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="privacies")
     */
    public function postAction(Request $request, User $user, PrivacyManager $privacyManager)
    {
        $privacy = $privacyManager->create($user->getId(), $request->request->all());

        return $this->view($privacy, 201);
    }

    /**
     * Update privacy
     *
     * @Put("/privacy")
     * @param User $user
     * @param Request $request
     * @param PrivacyManager $privacyManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns updated privacy",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="privacies")
     */
    public function putAction(Request $request, User $user, PrivacyManager $privacyManager)
    {
        $privacy = $privacyManager->update($user->getId(), $request->request->all());

        return $this->view($privacy, 200);
    }

    /**
     * Delete privacy
     *
     * @Delete("/privacy")
     * @param User $user
     * @param PrivacyManager $privacyManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted privacy",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="privacies")
     */
    public function deleteAction(User $user, PrivacyManager $privacyManager)
    {
        $privacy = $privacyManager->getById($user->getId());
        $privacyManager->remove($user->getId());

        return $this->view($privacy, 200);
    }

    /**
     * Get privacy metadata
     *
     * @Get("/privacy/metadata")
     * @param Request $request
     * @param PrivacyManager $privacyManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns privacy metadata",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="privacies")
     */
    public function getMetadataAction(Request $request, PrivacyManager $privacyManager)
    {
        $locale = $request->query->get('locale');
        $metadata = $privacyManager->getMetadata($locale);

        return $this->view($metadata, 200);
    }
}
