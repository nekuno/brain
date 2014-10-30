<?php

namespace Controller\User;

use Model\User\ProfileModel;
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
     * @return JsonResponse
     * @throws \Exception
     */
    public function getAction(Request $request, Application $app)
    {

        /* @var $profile array */
        $profile = $this->getProfile($request, $app);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];
        $metadata = $model->getMetadata();

        $result = array(
            'profile' => $profile,
            'metadata' => $metadata,
        );

        return $app->json($result, !empty($result) ? 200 : 404);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function postAction(Request $request, Application $app)
    {

        // TODO: Validate data

        try {
            /* @var $model ProfileModel */
            $model = $app['users.profile.model'];
            $result = $model->create($request->request->all());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function putAction(Request $request, Application $app)
    {

        // TODO: Validate data

        /* @var $profile array */
        $profile = $this->getProfile($request, $app);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];
        $metadata = $model->getMetadata();

        try {
            /* @var $model ProfileModel */
            $model = $app['users.profile.model'];
            $profile = $model->update($request->request->all());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        $result = array(
            'profile' => $profile,
            'metadata' => $metadata,
        );

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Request $request, Application $app)
    {

        /* @var $profile array */
        $profile = $this->getProfile($request, $app);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        try {
            /* @var $model ProfileModel */
            $model = $app['users.profile.model'];
            $id = $request->get('id');
            $model->remove($id);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array(), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse|array
     * @throws \Exception
     */
    protected function getProfile(Request $request, Application $app)
    {
        $id = $request->get('id');
        if (null === $id) {
            return $app->json(array(), 404);
        }

        try {
            /* @var $model ProfileModel */
            $model = $app['users.profile.model'];
            $profile = $model->getById($id);
        } catch (\Exception $e) {
            
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $profile;
    }

}
