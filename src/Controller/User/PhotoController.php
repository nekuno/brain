<?php

namespace Controller\User;

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

        $file = '';

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

        $manager->remove($id);

        return $app->json($photo);
    }

    public function validateAction(Request $request, Application $app)
    {

    }

}
