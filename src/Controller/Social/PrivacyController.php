<?php

namespace Controller\Social;

use Model\User\PrivacyModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PrivacyController
 * @package Controller
 */
class PrivacyController
{
    /**
     * @param Application $app
     * @param integer $id
     * @return JsonResponse
     */
    public function getAction(Application $app, $id)
    {
        /* @var $model PrivacyModel */
        $model = $app['users.privacy.model'];

        $privacy = $model->getById($id);

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
}
