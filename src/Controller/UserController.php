<?php

namespace Controller;

use Model\UserModel;
use Model\User\ContentPaginatorModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UserController
{

    public function indexAction(Request $request, Application $app)
    {

        try {
            $model  = $app['users.model'];
            $result = $model->getAll();
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, 200);
    }

    public function addAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (
                null == $request->request->get('id')
                || null == $request->request->get('username')
                || null == $request->request->get('email')
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
            $model  = $app['users.model'];
            $result = $model->create($request->request->all());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function showAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        if (null === $id) {
            return $app->json(array(), 404);
        }

        try {
            $model  = $app['users.model'];
            $result = $model->getById($request->get('id'));
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($user) ? 200 : 404);
    }

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

    public function getMatchingAction(Request $request, Application $app)
    {

        // Get params
        $id1     = $request->get('id1');
        $id2     = $request->get('id2');
        $basedOn = $request->get('type');

        if (null === $id1 || null === $id2) {
            return $app->json(array(), 400);
        }

        try {
            /** @var $model \Model\User\MatchingModel */
            $model = $app['users.matching.model'];
            if ($basedOn == 'answers') {
                $result = $model->getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2);
            }
            if ($basedOn == 'content') {
                $result = $model->getMatchingBetweenTwoUsersBasedOnSharedContent($id1, $id2);
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function getUserQuestionsAction(Request $request, Application $app)
    {
        $id   = $request->get('id');

        if (null === $id) {
            return $app->json(array(), 400);
        }

        /** @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id);

        /** @var $model \Model\User\QuestionPaginatedModel  */
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

    public function getUserQuestionsCompareAction(Request $request, Application $app)
    {
        $id   = $request->get('id');
        $id2   = $request->get('id2');
        $showOnlyCommon   = $request->get('showOnlyCommon', 0);

        if (null === $id || null === $id2) {
            return $app->json(array(), 400);
        }

        /** @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id, 'id2' => $id2, 'showOnlyCommon' => $showOnlyCommon);

        /** @var $model \Model\User\QuestionComparePaginatedModel  */
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

    public function getUserContentAction(Request $request, Application $app)
    {
        $id   = $request->get('id');
        $commonWithId  = $request->get('commonWithId', null);
        $tag  = $request->get('tag', null);
        $type = $request->get('type', null);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        /** @var $paginator \Paginator\Paginator */
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

        /** @var $model \Model\User\ContentPaginatorModel  */
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

    public function getUserContentCompareAction(Request $request, Application $app)
    {
        $id   = $request->get('id');
        $id2   = $request->get('id2');
        $tag  = $request->get('tag', null);
        $type = $request->get('type', null);
        $showOnlyCommon   = $request->get('showOnlyCommon', 0);

        if (null === $id || null === $id2) {
            return $app->json(array(), 400);
        }

        /** @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id, 'id2' => $id2, 'showOnlyCommon' => $showOnlyCommon);

        if ($tag) {
            $filters['tag'] = urldecode($tag);
        }

        if ($type) {
            $filters['type'] = urldecode($type);
        }

        /** @var $model \Model\User\ContentComparePaginatedModel  */
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

    public function getUserContentTagsAction(Request $request, Application $app)
    {
        $id     = $request->get('id');
        $search = $request->get('search', '');
        $limit  = $request->get('limit', 0);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        if ($search) {
            $search = urldecode($search);
        }

        /** @var $model \Model\User\ContentTagModel  */
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

    public function rateContentAction(Request $request, Application $app)
    {
        $id = $request->get('id');
        $url = $request->request->get('url');
        $rate = $request->request->get('rate');

        if (null == $id || null == $url || null == $rate) {
            return $app->json(array('text' => 'aaa', 'id'=> $id, 'url' => $url), 400);
        }

        try {
            $model  = $app['users.rate.model'];
            $result = $model->userRateLink($id, $url, $rate);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function getUserRecommendationAction(Request $request, Application $app)
    {
        // Get params
        $id      = $request->get('id');
        $basedOn = $request->get('type');

        if (null === $id) {
            return $app->json(array(), 400);
        }

        try {
            /** @var $model \Model\User\Recommendation\UserModel  */
            $model = $app['users.recommendation.users.model'];
            if ($basedOn == 'answers') {
                // TODO: check that users has one answered question at least
                $result = $model->getUserRecommendationsBasedOnAnswers($id);
            }
            if ($basedOn == 'content') {
                $result = $model->getUserRecommendationsBasedOnSharedContent($id);
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function getContentRecommendationAction(Request $request, Application $app)
    {
        $id      = $request->get('id');
        $tag     = $request->get('tag', null);
        $type     = $request->get('type', null);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        /** @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id);

        if ($tag) {
            $filters['tag'] = urldecode($tag);
        }

        if ($type) {
            $filters['type'] = urldecode($type);
        }

        /** @var $model \Model\User\Recommendation\ContentPaginatedModel  */
        $model = $app['users.recommendation.content.model'];

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

    public function getContentRecommendationTagsAction(Request $request, Application $app)
    {
        $id      = $request->get('id');
        $search     = $request->get('search', '');
        $limit     = $request->get('limit', 0);

        if (null === $id) {
            return $app->json(array(), 400);
        }

        if ($search) {
            $search = urldecode($search);
        }

        /** @var $model \Model\User\Recommendation\ContentRecommendationTagModel  */
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
} 
