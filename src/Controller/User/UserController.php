<?php

namespace Controller\User;

use Controller\BaseController;
use Model\User\ContentPaginatedModel;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Model\User\RateModel;
use Model\User\UserStatsManager;
use Manager\UserManager;
use Service\Recommendator;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserController
 * @package Controller
 */
class UserController extends BaseController
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

        $model = $app['users.manager'];

        $result = $paginator->paginate($filters, $model, $request);

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @return JsonResponse
     */
    public function getAction(Application $app)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->getById($this->getUserId())->jsonSerialize();
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $user['groups'] = $groupModel->getByUser($this->getUserId());

        return $app->json($user);
    }

    /**
     * @param Application $app
     * @param int $id
     * @return JsonResponse
     */
    public function getOtherAction(Application $app, $id)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->getById($id)->jsonSerialize();
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
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $criteria = $request->query->all();
        $user = isset($criteria['id']) ? $model->getById($criteria['id']) : $model->findUserBy($criteria);
        $return = $user->jsonSerialize();

        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $return['groups'] = $groupModel->getByUser($user->getId());

        return $app->json($return);
    }

    /**
     * @param Application $app
     * @param string $username
     * @throws NotFoundHttpException
     * @return JsonResponse
     */
    public function availableAction(Application $app, $username)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        try {
            $model->findUserBy(array('usernameCanonical' => mb_strtolower($username)));
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
        /* @var $model UserManager */
        $model = $app['users.manager'];
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
        /* @var $model UserManager */
        $model = $app['users.manager'];
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
        $data = $request->request->all();
        $data['userId'] = $this->getUserId();
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->update($data);

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
        $otherUserId = $request->get('id');

        if (null === $otherUserId) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Matching\MatchingModel */
            $model = $app['users.matching.model'];
            $result = $model->getMatchingBetweenTwoUsersBasedOnAnswers($this->getUserId(), $otherUserId);
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
        $otherUserId = $request->get('id');

        if (null === $otherUserId) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Similarity\SimilarityModel */
            $model = $app['users.similarity.model'];
            $similarity = $model->getCurrentSimilarity($this->getUserId(), $otherUserId);
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
    public function getUserContentAction(Request $request, Application $app)
    {
        $commonWithId = $request->get('commonWithId', null);
        $tag = $request->get('tag', null);
        $type = $request->get('type', null);

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $this->getUserId());

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
        $otherUserId = $request->get('id');
        $tag = $request->get('tag', null);
        $type = $request->get('type', null);
        $showOnlyCommon = $request->get('showOnlyCommon', 0);

        if (null === $otherUserId) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => (int)$this->getUserId(), 'id2' => (int)$otherUserId, 'showOnlyCommon' => (int)$showOnlyCommon);

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
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if ($search) {
            $search = urldecode($search);
        }

        /* @var $model \Model\User\ContentTagModel */
        $model = $app['users.content.tag.model'];

        try {
            $result = $model->getContentTags($this->getUserId(), $search, $limit);
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
        $rate = $request->request->get('rate');
        $data = $request->request->all();
        if (isset($data['linkId']) && !isset($data['id'])) {
            $data['id'] = $data['linkId'];
        }

        if (null == $data['linkId'] || null == $rate) {
            return $app->json(array('text' => 'Link Not Found', 'id' => $this->getUserId(), 'linkId' => $data['linkId']), 400);
        }

        try {
            /* @var RateModel $model */
            $model = $app['users.rate.model'];
            $result = $model->userRateLink($this->getUserId(), $data, $rate);
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
    public function getUserRecommendationAction(Request $request, Application $app)
    {
        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getUserRecommendationFromRequest($request, $this->getUserId());
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
        $linkId = $request->get('linkId');

        if (null === $linkId) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Affinity\AffinityModel */
            $model = $app['users.affinity.model'];
            $affinity = $model->getAffinity($this->getUserId(), $linkId);
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
     * @return JsonResponse
     * @throws \Exception
     */
    public function getContentRecommendationAction(Request $request, Application $app)
    {

        /* @var $recommendator Recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getContentRecommendationFromRequest($request, $this->getUserId());
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
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if ($search) {
            $search = urldecode($search);
        }

        /* @var $model \Model\User\Recommendation\ContentRecommendationTagModel */
        $model = $app['users.recommendation.content.tag.model'];

        try {
            $result = $model->getRecommendedTags($this->getUserId(), $search, $limit);
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
        $filters = array();
        /* @var $model ProfileModel */
        $profileModel = $app['users.profile.model'];
        $filters['profileFilters'] = $profileModel->getFilters($locale);

        //user-dependent filters
        $dynamicFilters = array();
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $dynamicFilters['groups'] = $groupModel->getByUser($this->getUserId());
        /* @var $userManager UserManager */
        $userManager = $app['users.manager'];
        $filters['userFilters'] = $userManager->getFilters($locale, $dynamicFilters);

        return $app->json($filters, 200);
    }

    /**
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function statusAction(Application $app)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];

        $status = $model->getStatus($this->getUserId());

        return $app->json(array('status' => $status));
    }

    public function statsAction(Application $app)
    {
        /* @var $manager UserStatsManager */
        $manager = $app['users.stats.manager'];

        $stats = $manager->getStats($this->getUserId());

        return $app->json($stats->toArray());
    }

    public function statsCompareAction(Request $request, Application $app)
    {
        $otherUserId = $request->get('id');
        if (null === $otherUserId) {
            throw new NotFoundHttpException('User not found');
        }
        if ($this->getUserId() === $otherUserId) {
            return $app->json(array(), 400);
        }
        /* @var $model UserManager */
        $model = $app['users.manager'];

        $stats = $model->getComparedStats($this->getUserId(), $otherUserId);

        return $app->json($stats->toArray());

    }
}
