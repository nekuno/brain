<?php

namespace Controller\User;

use Model\User\ContentPaginatedModel;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Model\User\RateModel;
use Model\UserModel;
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
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function indexAction(Request $request, Application $app)
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
    public function addAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (null == $request->request->get('id') || null == $request->request->get('username')
            ) {
                return $app->json(array(), 400);
            }

            if (!is_int($request->request->get('id'))) {
                return $app->json(array(), 400);
            }
        } else {
            return $app->json(array(), 400);
        }

        // Create and persist the User

        try {
            /* @var $model UserModel */
            $model = $app['users.model'];
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function showAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        if (null === $id) {
            return $app->json(array(), 404);
        }

        try {
            $model = $app['users.model'];
            $result = $model->getById($request->get('id'));
            $groupModel = $app['users.groups.model'];
            $result['groups'] = $groupModel->getByUser((integer)($request->get('id')));
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 200 : 404);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Request $request, Application $app)
    {

        $id = $request->get('id');

        if (null === $id) {
            return $app->json(array(), 400);
        }

        try {
            $model = $app['users.model'];
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
            $similarity = $model->getSimilarity($id1, $id2);
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

        $filters = array('id' => $id, 'id2' => $id2, 'showOnlyCommon' => $showOnlyCommon);

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
        $linkId = $request->request->get('linkId');
        $rate = $request->request->get('rate');

        if (null == $userId || null == $linkId || null == $rate) {
            return $app->json(array('text' => 'Link Not Found', 'id' => $userId, 'linkId' => $linkId), 400);
        }

        try {
            /* @var RateModel $model */
            $model = $app['users.rate.model'];
            $result = $model->userRateLink($userId, $linkId, $rate);
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
    public function getUserRecommendationAction(Request $request, Application $app)
    {

        // Get params
        $id = $request->get('id');
        $order = $request->get('order', false);
        $filters = $request->get('filters', array());

        if (null === $id) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array(
            'id' => $id,
            'profileFilters' => $filters,
            'groups' => isset($filters['groups']) ? $filters['groups'] : array()
        );

        if ($order) {
            $filters['order'] = $order;
        }

        /** @var GroupModel $groupModel */
        $groupModel = $app['users.groups.model'];
        foreach ($filters['groups'] as $groupName) {
            if (!$groupModel->isUserFromGroup($groupName, $id)) {
                return $app->json(array(), 403);
            }
        }

        /* @var $model \Model\User\Recommendation\UserRecommendationPaginatedModel */
        $model = $app['users.recommendation.users.model'];

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getContentRecommendationAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $tag = $request->get('tag', null);
        $type = $request->get('type', null);
        $foreign = $request->get('foreign', null);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id);

        if ($tag) {
            $filters['tag'] = urldecode($tag);
        }

        if ($type) {
            $filters['type'] = urldecode($type);
        }

        if ($foreign) {
            $filters['foreign'] = urldecode($foreign);
        }

        /* @var $model \Model\User\Recommendation\ContentRecommendationPaginatedModel */
        $model = $app['users.recommendation.content.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        $newForeign = 0;
        foreach ($result['items'] as $item) {
            if ($item['match'] == 0) {
                $newForeign++;
            }
        }

        if ($result['pagination']['nextLink'] != null) {
            if ($foreign != null) {
                $result['pagination']['nextLink'] = str_replace(
                    'foreign=' . $foreign,
                    'foreign=' . ($foreign + $newForeign),
                    $result['pagination']['nextLink']
                );
            } else {
                $result['pagination']['nextLink'] .= ('&foreign=' . $newForeign);
            }
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
        $filters = array_merge($filters, $profileModel->getFilters($locale));

        /** @var GroupModel $groupModel */
        $groupModel = $app['users.groups.model'];
        $groups = $groupModel->getByUser((integer)$id);
        /* @var $model UserModel */
        $userModel = $app['users.model'];
        $filters = array_merge($filters, $userModel->getFilters($locale, $groups));

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

        /* @var $model UserModel */
        $model = $app['users.model'];

        $stats = $model->getStats($id);

        return $app->json($stats->toArray());
    }

    public function statsCompareAction(Request $request, Application $app)
    {
        $id1 = (integer)$request->get('id1');
        $id2 = (integer)$request->get('id2');
        if (null === $id1 || null === $id2) {
            throw new NotFoundHttpException('User not found');
        }
        if ($id1 === $id2){
            return $app->json(array(), 400);
        }
        /* @var $model UserModel */
        $model = $app['users.model'];

        $stats = $model->getComparedStats($id1, $id2);

        return $app->json($stats->toArray());

    }
}
