<?php

namespace Controller\Social;

use Model\User\Group\GroupModel;
use Manager\UserManager;
use Model\User;
use Model\User\ProfileFilterModel;
use Service\Recommendator;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserController
 * @package Controller
 */
class UserController
{
    /**
     * @param Application $app
     * @param int $id
     * @return JsonResponse
     */
    public function getAction(Application $app, $id)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $userArray = $model->getById($id)->jsonSerialize();

        return $app->json($userArray);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function findAction(Application $app, Request $request)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $criteria = $request->query->all();
        $user = isset($criteria['id']) ? $model->getById($criteria['id']) : $model->findUserBy($criteria);
        $userArray = $user->jsonSerialize();

        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $userArray['groups'] = $groupModel->getByUser($user->getId());

        return $app->json($userArray);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param integer $id
     * @return JsonResponse
     */
    public function putAction(Application $app, Request $request, $id)
    {
        $data = $request->request->all();
        $data['userId'] = (int)$id;
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->update($data);

        return $app->json($user);
    }

    public function jwtAction(Application $app, $id)
    {
        $authService = $app['auth.service'];
        $jwt = $authService->getToken($id);

        return $app->json(array('jwt' => $jwt));
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param $id
     * @return JsonResponse
     */
    public function getAllFiltersAction(Request $request, Application $app, $id)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->getById($id);
        $locale = $request->query->get('locale');
        $filters = array();

        /* @var $profileFilterModel ProfileFilterModel */
        $profileFilterModel = $app['users.profileFilter.model'];
        $filters['profileFilters'] = $profileFilterModel->getSocialFilters($locale);

        //user-dependent filters

        /* @var $userFilterModel User\UserFilterModel */
        $userFilterModel = $app['users.userFilter.model'];
        $userFilters = $userFilterModel->getSocialFilters($locale);

        /* @var $groupModel \Model\User\Group\GroupModel */
        $groupModel = $app['users.groups.model'];
        $groups = $groupModel->getByUser($user->getId());

        $userFilters['groups']['choices'] = array();
        foreach ($groups as $group) {
            $userFilters['groups']['choices'][$group->getId()] = $group->getName();
        }

        if ($groups = null || $groups == array()) {
            unset($userFilters['groups']);
        }

        $filters['userFilters'] = $userFilters;

        return $app->json($filters, 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param int $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function getUserRecommendationAction(Request $request, Application $app, $id)
    {
        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getUserRecommendationFromRequest($request, $id, true);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result);
    }
}
