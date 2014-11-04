<?php

namespace Controller\User;

use Model\Exception\ValidationException;
use Model\User\ProfileModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        try {
            $profile = $model->getById($request->get('id'));
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

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

        $id = $request->get('id');
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];
        $metadata = $model->getMetadata();

        try {
            $profile = $model->create($id, $request->request->all());
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        } catch (ValidationException $e) {
            return $app->json(array('validationErrors' => $e->getErrors()), 500);
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
    public function putAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];
        $metadata = $model->getMetadata();

        try {
            $profile = $model->update($id, $request->request->all());
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        } catch (ValidationException $e) {
            return $app->json(array('validationErrors' => $e->getErrors()), 500);
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

        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        try {
            $id = $request->get('id');
            $profile = $model->getById($id);
            $model->remove($id);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        return $app->json(array(), 200);
    }

    /**
     * @param Application $app
     * @return JsonResponse
     */
    public function getMetadataAction(Application $app)
    {

        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];
        $metadata = $model->getMetadata();

        return $app->json($metadata);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function validateAction(Request $request, Application $app)
    {
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        try {
            $model->validate($request->request->all());
        } catch (ValidationException $e) {
            return $app->json($e->getErrors(), 500);
        }

        return $app->json(array());
    }

}
