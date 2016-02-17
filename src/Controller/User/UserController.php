<?php

namespace Controller\User;

use Model\User\ContentPaginatedModel;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Model\User\RateModel;
use Model\User\UserStatsManager;
use Model\UserModel;
use Service\Recommendator;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserController
 * @package Controller
 */
class UserController
{

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function indexAction(Application $app, Request $request)
    {

        $filters = array();

        $referenceUserId = $request->get('referenceUserId');
        if (null != $referenceUserId) {
            $filters['referenceUserId'] = $referenceUserId;
        }

        $profile = $request->get('profile');
        if (null != $profile) {
            $filters['profile'] = $profile;
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $model = $app['users.model'];

        $result = $paginator->paginate($filters, $model, $request);

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function getAction(Application $app, Request $request)
    {

        $userId = $request->request->get('userId');
        /* @var $model UserModel */
        $model = $app['users.model'];
        $user = $model->getById($userId);
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $user['groups'] = $groupModel->getByUser($userId);

        return $app->json($user);
    }

    /**
     * @param Application $app
     * @param int $id
     * @return JsonResponse
     */
    public function getOtherAction(Application $app, $id)
    {

        /* @var $model UserModel */
        $model = $app['users.model'];
        $user = $model->getById($id);
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $user['groups'] = $groupModel->getByUser($id);

        return $app->json($user);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function findAction(Application $app, Request $request)
    {

        /* @var $model UserModel */
        $model = $app['users.model'];
        $criteria = $request->query->all();
        $user = isset($criteria['id']) ? $model->getById($criteria['id']) : $model->findBy($criteria);
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $user['groups'] = $groupModel->getByUser($user['qnoow_id']);

        return $app->json($user);
    }

    /**
     * @param Application $app
     * @param string $username
     * @throws NotFoundHttpException
     * @return JsonResponse
     */
    public function availableAction(Application $app, $username)
    {

        /* @var $model UserModel */
        $model = $app['users.model'];
        try {
            $model->findBy(array('usernameCanonical' => mb_strtolower($username)));
        } catch (NotFoundHttpException $e) {
            return $app->json();
        }

        throw new NotFoundHttpException('Username not available');
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function validateAction(Request $request, Application $app)
    {

        /* @var $model UserModel */
        $model = $app['users.model'];

        $model->validate($request->request->all());

        return $app->json();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function postAction(Application $app, Request $request)
    {

        /* @var $model UserModel */
        $model = $app['users.model'];
        $user = $model->create($request->request->all());

        return $app->json($user, 201);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function putAction(Application $app, Request $request)
    {

        /* @var $model UserModel */
        $model = $app['users.model'];
        $user = $model->update($request->request->all());

        return $app->json($user);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getMatchingAction(Request $request, Application $app)
    {

        // Get params
        $id1 = $request->get('id1');
        $id2 = $request->get('id2');

        if (null === $id1 || null === $id2) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Matching\MatchingModel */
            $model = $app['users.matching.model'];
            $result = $model->getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getSimilarityAction(Request $request, Application $app)
    {

        // Get params
        $id1 = $request->get('id1');
        $id2 = $request->get('id2');

        if (null === $id1 || null === $id2) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Similarity\SimilarityModel */
            $model = $app['users.similarity.model'];
            $similarity = $model->getCurrentSimilarity($id1, $id2);
            $result = array('similarity' => $similarity['similarity']);

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserQuestionsAction(Request $request, Application $app)
    {

        $id = $request->get('id');

        if (null === $id) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id);

        /* @var $model \Model\User\QuestionPaginatedModel */
        $model = $app['users.questions.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getOldUserQuestionsCompareAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $id2 = $request->get('id2');
        $locale = $request->get('locale');
        $showOnlyCommon = $request->get('showOnlyCommon', 0);

        if (null === $id || null === $id2) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id, 'id2' => $id2, 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon);

        /* @var $model \Model\User\OldQuestionComparePaginatedModel */
        $model = $app['old.users.questions.compare.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserQuestionsCompareAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $id2 = $request->get('id2');
        $locale = $request->get('locale');
        $showOnlyCommon = $request->get('showOnlyCommon', 0);

        if (null === $id || null === $id2) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id, 'id2' => $id2, 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon);

        /* @var $model \Model\User\QuestionComparePaginatedModel */
        $model = $app['users.questions.compare.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserContentAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $commonWithId = $request->get('commonWithId', null);
        $tag = $request->get('tag', null);
        $type = $request->get('type', null);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id);

        if ($commonWithId) {
            $filters['commonWithId'] = (int)$commonWithId;
        }

        if ($tag) {
            $filters['tag'] = urldecode($tag);
        }

        if ($type) {
            $filters['type'] = urldecode($type);
        }

        /* @var $model ContentPaginatedModel */
        $model = $app['users.content.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserContentCompareAction(Request $request, Application $app)
    {
        $id = $request->get('id');
        $id2 = $request->get('id2');
        $tag = $request->get('tag', null);
        $type = $request->get('type', null);
        $showOnlyCommon = $request->get('showOnlyCommon', 0);

        if (null === $id || null === $id2) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => (int)$id, 'id2' => (int)$id2, 'showOnlyCommon' => (int)$showOnlyCommon);

        if ($tag) {
            $filters['tag'] = urldecode($tag);
        }

        if ($type) {
            $filters['type'] = urldecode($type);
        }

        /* @var $model \Model\User\ContentComparePaginatedModel */
        $model = $app['users.content.compare.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserContentTagsAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        if ($search) {
            $search = urldecode($search);
        }

        /* @var $model \Model\User\ContentTagModel */
        $model = $app['users.content.tag.model'];

        try {
            $result = $model->getContentTags($id, $search, $limit);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function rateContentAction(Request $request, Application $app)
    {
        $userId = $request->get('id');
        $rate = $request->request->get('rate');
        $data = $request->request->all();
        if (isset($data['linkId']) && !isset($data['id'])) {
            $data['id'] = $data['linkId'];
        }

        if (null == $userId || null == $data['linkId'] || null == $rate) {
            return $app->json(array('text' => 'Link Not Found', 'id' => $userId, 'linkId' => $data['linkId']), 400);
        }

        try {
            /* @var RateModel $model */
            $model = $app['users.rate.model'];
            $result = $model->userRateLink($userId, $data, $rate);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function getUserRecommendationAction(Request $request, Application $app, $id)
    {
        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getUserRecommendationFromRequest($request, $id);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAffinityAction(Request $request, Application $app)
    {

        $userId = $request->get('userId');
        $linkId = $request->get('linkId');

        if (null === $userId || null === $linkId) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Affinity\AffinityModel */
            $model = $app['users.affinity.model'];
            $affinity = $model->getAffinity($userId, $linkId);
            $result = array('affinity' => $affinity['affinity']);
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
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function getContentRecommendationAction(Request $request, Application $app, $id)
    {

        /* @var $recommendator Recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getContentRecommendationFromRequest($request, $id);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getContentRecommendationTagsAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        if ($search) {
            $search = urldecode($search);
        }

        /* @var $model \Model\User\Recommendation\ContentRecommendationTagModel */
        $model = $app['users.recommendation.content.tag.model'];

        try {
            $result = $model->getRecommendedTags($id, $search, $limit);
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
     */
    public function getAllFiltersAction(Request $request, Application $app)
    {
        $locale = $request->query->get('locale');
        $id = $request->get('id');
        $filters = array();
        /* @var $model ProfileModel */
        $profileModel = $app['users.profile.model'];
        $filters['profileFilters'] = $profileModel->getFilters($locale);

        //user-dependent filters
        $dynamicFilters = array();
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $dynamicFilters['groups'] = $groupModel->getByUser((integer)$id);
        /* @var $userModel UserModel */
        $userModel = $app['users.model'];
        $filters['userFilters'] = $userModel->getFilters($locale, $dynamicFilters);

        return $app->json($filters, 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function statusAction(Request $request, Application $app)
    {

        $id = (integer)$request->get('id');
        if (null === $id) {
            throw new NotFoundHttpException('User not found');
        }

        /* @var $model UserModel */
        $model = $app['users.model'];

        $status = $model->getStatus($id);

        return $app->json(array('status' => $status));
    }

    public function statsAction(Request $request, Application $app)
    {

        $id = (integer)$request->get('id');
        if (null === $id) {
            throw new NotFoundHttpException('User not found');
        }

        /* @var $manager UserStatsManager */
        $manager = $app['users.stats.manager'];

        $stats = $manager->getStats($id);

        return $app->json($stats->toArray());
    }

    public function statsCompareAction(Request $request, Application $app)
    {
        $id1 = (integer)$request->get('id1');
        $id2 = (integer)$request->get('id2');
        if (null === $id1 || null === $id2) {
            throw new NotFoundHttpException('User not found');
        }
        if ($id1 === $id2) {
            return $app->json(array(), 400);
        }
        /* @var $model UserModel */
        $model = $app['users.model'];

        $stats = $model->getComparedStats($id1, $id2);

        return $app->json($stats->toArray());

    }
}
