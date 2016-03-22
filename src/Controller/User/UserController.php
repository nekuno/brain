<?php

namespace Controller\User;

use Model\User\ContentPaginatedModel;
use Model\User\GroupModel;
use Model\User\ProfileFilterModel;
use Model\User\RateModel;
use Model\User\UserStatsManager;
use Manager\UserManager;
use Model\User;
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

        $model = $app['users.manager'];

        $result = $paginator->paginate($filters, $model, $request);

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param User $user
     * @return JsonResponse
     */
    public function getAction(Application $app, User $user)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $userArray = $model->getById($user->getId())->jsonSerialize();
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $userArray['groups'] = $groupModel->getByUser($user->getId());

        return $app->json($userArray);
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
        $userArray = $model->getById($id)->jsonSerialize();

        if (empty($userArray)) {
            return $app->json([], 404);
        }

        unset($userArray['password']);
        unset($userArray['salt']);

        return $app->json($userArray);
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
     * @param User $user
     * @return JsonResponse
     */
    public function putAction(Application $app, Request $request, User $user)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->update($data);

        return $app->json($user);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getMatchingAction(Request $request, Application $app, User $user)
    {
        $otherUserId = $request->get('id');

        if (null === $otherUserId) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Matching\MatchingModel */
            $model = $app['users.matching.model'];
            $result = $model->getMatchingBetweenTwoUsersBasedOnAnswers($user->getId(), $otherUserId);
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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getSimilarityAction(Request $request, Application $app, User $user)
    {
        $otherUserId = $request->get('id');

        if (null === $otherUserId) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Similarity\SimilarityModel */
            $model = $app['users.similarity.model'];
            $similarity = $model->getCurrentSimilarity($user->getId(), $otherUserId);
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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserContentAction(Request $request, Application $app, User $user)
    {
        $commonWithId = $request->get('commonWithId', null);
        $tag = $request->get('tag', null);
        $type = $request->get('type', null);

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $user->getId());

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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserContentCompareAction(Request $request, Application $app, User $user)
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

        $filters = array('id' => $otherUserId, 'id2' => $user->getId(), 'showOnlyCommon' => (int)$showOnlyCommon);

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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserContentTagsAction(Request $request, Application $app, User $user)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if ($search) {
            $search = urldecode($search);
        }

        /* @var $model \Model\User\ContentTagModel */
        $model = $app['users.content.tag.model'];

        try {
            $result = $model->getContentTags($user->getId(), $search, $limit);
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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function rateContentAction(Request $request, Application $app, User $user)
    {
        $rate = $request->request->get('rate');
        $data = $request->request->all();
        if (isset($data['linkId']) && !isset($data['id'])) {
            $data['id'] = $data['linkId'];
        }

        if (null == $data['linkId'] || null == $rate) {
            return $app->json(array('text' => 'Link Not Found', 'id' => $user->getId(), 'linkId' => $data['linkId']), 400);
        }

        try {
            /* @var RateModel $model */
            $model = $app['users.rate.model'];
            $result = $model->userRateLink($user->getId(), $data, $rate);
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
     * @param User $user
     * @return JsonResponse
     * @throws \Exception
     */
    public function getUserRecommendationAction(Request $request, Application $app, User $user)
    {
        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getUserRecommendationFromRequest($request, $user->getId());
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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAffinityAction(Request $request, Application $app, User $user)
    {
        $linkId = $request->get('linkId');

        if (null === $linkId) {
            return $app->json(array(), 400);
        }

        try {
            /* @var $model \Model\User\Affinity\AffinityModel */
            $model = $app['users.affinity.model'];
            $affinity = $model->getAffinity($user->getId(), $linkId);
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
     * @param User $user
     * @return JsonResponse
     * @throws \Exception
     */
    public function getContentRecommendationAction(Request $request, Application $app, User $user)
    {

        /* @var $recommendator Recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getContentRecommendationFromRequest($request, $user->getId());
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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getContentRecommendationTagsAction(Request $request, Application $app, User $user)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if ($search) {
            $search = urldecode($search);
        }

        /* @var $model \Model\User\Recommendation\ContentRecommendationTagModel */
        $model = $app['users.recommendation.content.tag.model'];

        try {
            $result = $model->getRecommendedTags($user->getId(), $search, $limit);
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
     * @param User $user
     * @return JsonResponse
     */
    public function getAllFiltersAction(Request $request, Application $app, User $user)
    {
        $locale = $request->query->get('locale');
        $filters = array();
        /* @var $profileFilterModel ProfileFilterModel */
        $profileFilterModel = $app['users.profileFilter.model'];
        $filters['profileFilters'] = $profileFilterModel->getFilters($locale);

        /* @var $userFilterModel User\UserFilterModel */
        $userFilterModel = $app['users.userFilter.model'];
        $filters['userFilters'] = $userFilterModel->getFilters($locale, $user);

        return $app->json($filters, 200);
    }

    /**
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function statusAction(Application $app, User $user)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];

        $status = $model->getStatus($user->getId());

        return $app->json(array('status' => $status));
    }

    /**
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function statsAction(Application $app, User $user)
    {
        /* @var $manager UserStatsManager */
        $manager = $app['users.stats.manager'];

        $stats = $manager->getStats($user->getId());

        return $app->json($stats->toArray());
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function statsCompareAction(Request $request, Application $app, User $user)
    {
        $otherUserId = $request->get('id');
        if (null === $otherUserId) {
            throw new NotFoundHttpException('User not found');
        }
        if ($user->getId() === $otherUserId) {
            return $app->json(array(), 400);
        }
        /* @var $model UserManager */
        $model = $app['users.manager'];

        $stats = $model->getComparedStats($user->getId(), $otherUserId);

        return $app->json($stats->toArray());
    }
}
