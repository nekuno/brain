<?php

namespace Controller\User;

use Model\Exception\ValidationException;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class PhotoController
 * @package Controller
 */
class PhotoController
{

    public function getAllAction(Application $app, User $user)
    {

        $manager = $app['users.photo.manager'];

        $photos = $manager->getAll($user->getId());

        return $app->json($photos);
    }

    public function getAction(Application $app, User $user, $id)
    {

        $manager = $app['users.photo.manager'];

        $photo = $manager->getById($id);

        if ($photo->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Photo not allowed');
        }

        return $app->json($photo);
    }

    public function postAction(Application $app, Request $request, User $user)
    {

        $manager = $app['users.photo.manager'];

        if ($request->request->has('base64')) {
            $base64 = $request->request->get('base64');
            $file = base64_decode($base64);
        } else {
            if ($request->request->has('url')) {
                $url = $request->request->get('url');
                if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                    throw new ValidationException(array('photo' => array('Invalid "url" provided')));
                }
                $file = file_get_contents($url);
                if (!$file) {
                    throw new ValidationException(array('photo' => array('Unable to get photo from "url"')));
                }
            } else {
                throw new ValidationException(array('photo' => array('Invalid photo provided, param "base64" or "url" must be provided')));
            }
        }

        $photo = $manager->create($user->getId(), $file);

        return $app->json($photo, 201);
    }

    public function deleteAction(Application $app, User $user, $id)
    {

        $manager = $app['users.photo.manager'];

        $photo = $manager->getById($id);

        if ($photo->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Photo not allowed');
        }

        $success = $manager->remove($id);

        if ($success && is_writable($photo->getFullPath())) {
            unlink($photo->getFullPath());
        }

        return $app->json($photo);
    }

    public function validateAction(Request $request, Application $app)
    {

    }

}
