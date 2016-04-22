<?php

namespace Controller\Social;

use Model\User\ProfileFilterModel;
use Model\User\ProfileModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ProfileController
 * @package Controller
 */
class ProfileController
{
    /**
     * @param Request $request
     * @param Application $app
     * @param integer $id
     * @return JsonResponse
     */
    public function getAction(Request $request, Application $app, $id)
    {
        $locale = $request->query->get('locale');
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        $profile = $model->getById($id, $locale);

        return $app->json($profile);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param integer $id
     * @return JsonResponse
     */
    public function postAction(Request $request, Application $app, $id)
    {
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        $profile = $model->create($id, $request->request->all());

        return $app->json($profile, 201);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param integer $id
     * @return JsonResponse
     */
    public function putAction(Request $request, Application $app, $id)
    {
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        $profile = $model->update($id, $request->request->all());

        return $app->json($profile);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function getMetadataAction(Request $request, Application $app)
    {
        $locale = $request->query->get('locale');

        /* @var $model ProfileFilterModel */
        $model = $app['users.profileFilter.model'];
        $metadata = $model->getSocialMetadata($locale);

        return $app->json($metadata);
    }
}
