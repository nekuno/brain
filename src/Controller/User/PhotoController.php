<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Event\UserEvent;
use Model\Photo\PhotoManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Swagger\Annotations as SWG;

class PhotoController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get photos
     *
     * @Get("/photos")
     * @param User $user
     * @param PhotoManager $photoManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns photos.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="photos")
     */
    public function getAction(User $user, PhotoManager $photoManager)
    {
        $photos = $photoManager->getAll($user->getId());

        return $this->view($photos, 200);
    }

    /**
     * Get other user photos
     *
     * @Get("/photos/{userId}")
     * @param integer $userId
     * @param PhotoManager $photoManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns other user photos.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="photos")
     */
    public function getOtherAction($userId, PhotoManager $photoManager)
    {
        $photos = $photoManager->getAll($userId);

        return $this->view($photos, 200);
    }

    /**
     * Create photo
     *
     * @Post("/photos")
     * @param User $user
     * @param Request $request
     * @param PhotoManager $photoManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="base64", type="string"),
     *          @SWG\Property(property="url", type="string"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created photo.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="photos")
     */
    public function postAction(User $user, Request $request, PhotoManager $photoManager)
    {
        $file = $this->getPostFile($request, $photoManager);
        $photo = $photoManager->create($user, $file);

        return $this->view($photo, 201);
    }

    /**
     * Set photo as profile photo
     *
     * @Post("/photos/{photoId}/profile")
     * @param integer $photoId
     * @param User $user
     * @param Request $request
     * @param PhotoManager $photoManager
     * @param EventDispatcher $dispatcher
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="x", type="integer"),
     *          @SWG\Property(property="y", type="integer"),
     *          @SWG\Property(property="width", type="integer"),
     *          @SWG\Property(property="height", type="integer"),
     *          example={ "x" = 0, "y" = 0, "width" = 100, "height" = 100 },
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns user.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="photos")
     */
    public function postProfileAction($photoId, User $user, Request $request, PhotoManager $photoManager, EventDispatcher $dispatcher)
    {
        $xPercent = $request->request->get('x', 0);
        $yPercent = $request->request->get('y', 0);
        $widthPercent = $request->request->get('width', 100);
        $heightPercent = $request->request->get('height', 100);

        $photo = $photoManager->getById($photoId);

        if ($photo->getUserId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Photo not allowed');
        }

        $oldPhoto = $user->getPhoto();

        $photoManager->setAsProfilePhoto($photo, $user, $xPercent, $yPercent, $widthPercent, $heightPercent);
        $dispatcher->dispatch(\AppEvents::USER_PHOTO_CHANGED, new UserEvent($user));

        $oldPhoto->delete();

        return $this->view($user, 200);

    }

    /**
     * Delete photo
     *
     * @Delete("/photos/{photoId}")
     * @param integer $photoId
     * @param User $user
     * @param PhotoManager $photoManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted photo.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="photos")
     */
    public function deleteAction($photoId, User $user, PhotoManager $photoManager)
    {
        $photo = $photoManager->getById($photoId);

        if ($photo->getUserId() !== $user->getId()) {
            return $this->view('Photo not allowed', 403);
        }

        $photoManager->remove($photoId);

        return $this->view($photo, 200);
    }

    protected function getPostFile(Request $request, PhotoManager $photoManager)
    {
        if ($request->request->has('base64')) {
            $file = base64_decode($request->request->get('base64'));
            if (!$file) {
                $photoManager->throwPhotoException('Invalid "base64" provided');
            }

            return $file;
        }

        if ($request->request->has('url')) {
            $url = $request->request->get('url');
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                $photoManager->throwPhotoException('Invalid "url" provided');
            }

            $file = @file_get_contents($url);
            if (!$file) {
                $photoManager->throwPhotoException('Unable to get photo from "url"');
            }

            return $file;
        }

        $photoManager->throwPhotoException('Invalid photo provided, param "base64" or "url" must be provided');
        return null;
    }
}
