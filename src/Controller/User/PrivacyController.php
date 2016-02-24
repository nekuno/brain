<?php

namespace Controller\User;

use Model\User\PrivacyModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PrivacyController
 * @package Controller
 */
class PrivacyController
{
    /**
     * @param Application $app
     * @param User $user
     * @return JsonResponse
     */
    public function getAction(Application $app, User $user)
    {
        /* @var $model PrivacyModel */
        $model = $app['users.privacy.model'];

        $privacy = $model->getById($user->getId());

        return $app->json($privacy);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return JsonResponse
     */
    public function postAction(Request $request, Application $app, User $user)
    {
        /* @var $model PrivacyModel */
        $model = $app['users.privacy.model'];

        $privacy = $model->create($user->getId(), $request->request->all());

        return $app->json($privacy, 201);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return JsonResponse
     */
    public function putAction(Request $request, Application $app, User $user)
    {
        /* @var $model PrivacyModel */
        $model = $app['users.privacy.model'];

        $privacy = $model->update($user->getId(), $request->request->all());

        return $app->json($privacy);
    }

    /**
     * @param Application $app
     * @param User $user
     * @return JsonResponse
     */
    public function deleteAction(Application $app, User $user)
    {
        /* @var $model PrivacyModel */
        $model = $app['users.privacy.model'];

        $privacy = $model->getById($user->getId());
        $model->remove($user->getId());

        return $app->json($privacy);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function getMetadataAction(Request $request, Application $app)
    {
        $locale = $request->query->get('locale');

        /* @var $model PrivacyModel */
        $model = $app['users.privacy.model'];
        $metadata = $model->getMetadata($locale);

        return $app->json($metadata);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function validateAction(Request $request, Application $app)
    {
        /* @var $model PrivacyModel */
        $model = $app['users.privacy.model'];

        $model->validate($request->request->all());

        return $app->json();
    }
}
