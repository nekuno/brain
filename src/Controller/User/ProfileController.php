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

        $id = $request->get('id');
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        try {
            $profile = $model->getById($id);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        return $app->json($profile, 200);
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

        try {
            $profile = $model->create($id, $request->request->all());
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        } catch (ValidationException $e) {
            return $app->json(array('validationErrors' => $e->getErrors()), 500);
        }

        return $app->json($profile, 201);
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

        try {
            $profile = $model->update($id, $request->request->all());
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        } catch (ValidationException $e) {
            return $app->json(array('validationErrors' => $e->getErrors()), 500);
        }

        return $app->json($profile, 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        /* @var $model ProfileModel */
        $model = $app['users.profile.model'];

        try {
            $profile = $model->getById($id);
            $model->remove($id);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        return $app->json($profile, 200);
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

        return $app->json($metadata, 200);
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

        return $app->json(array(), 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getProfileTagsAction(Request $request, Application $app)
    {

        $type = $request->get('type');
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if (null === $type) {
            return $app->json(array(), 400);
        }

        if ($search) {
            $search = urldecode($search);
        }

        /** @var $model \Model\User\ProfileTagModel */
        $model = $app['users.profile.tag.model'];

        try {
            $result = $model->getProfileTags($type, $search, $limit);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }
}
