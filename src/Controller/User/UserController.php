<?php

namespace Controller\User;

use Model\User\ContentPaginatedModel;
use Model\User\Group\GroupModel;
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
use Model\User\TokensModel;
use Model\User\SocialNetwork\SocialProfile;
use ApiConsumer\Factory\ResourceOwnerFactory;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use Model\User\GhostUser\GhostUserManager;
use Model\User\SocialNetwork\SocialProfileManager;

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
        $userArray = $model->deleteOtherUserFields($userArray);

        if (empty($userArray)) {
            return $app->json([], 404);
        }

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
        /* @var $user User */
        $user = $app['user'];
        if ($user && mb_strtolower($username) === $user->getUsernameCanonical()) {
            return $app->json();
        }
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
        $data = $request->request->all();
        if (isset($data['oauth'])) {
            $oauthData = $data['oauth'];
            unset($data['oauth']);
        }
        /* @var $userManager UserManager */
        $userManager = $app['users.manager'];
        $user = $userManager->create($data);

        if (isset($data['enabled']) && $data['enabled'] === false) {
            $app['users.ghostuser.manager']->saveAsGhost($user->getId());
        }

        if (isset($oauthData)) {
            /* @var $tokensModel TokensModel */
            $tokensModel = $app['users.tokens.model'];
            $resourceOwner = $oauthData['resourceOwner'];

            $token = $tokensModel->create($user->getId(), $resourceOwner, $oauthData);

            /* @var $resourceOwnerFactory ResourceOwnerFactory */
            $resourceOwnerFactory = $app['api_consumer.resource_owner_factory'];

            if ($resourceOwner === TokensModel::FACEBOOK) {

                /* @var $facebookResourceOwner FacebookResourceOwner */
                $facebookResourceOwner = $resourceOwnerFactory->build(TokensModel::FACEBOOK);

                $token = $facebookResourceOwner->extend($token);

                if (array_key_exists('refreshToken', $token) && is_null($token['refreshToken'])) {
                    $token = $facebookResourceOwner->forceRefreshAccessToken($token);
                }
            }

            // TODO: This will not be executed since we only use Facebook for registration
            if ($resourceOwner == TokensModel::TWITTER) {
                $resourceOwnerObject = $resourceOwnerFactory->build($resourceOwner);
                $profileUrl = $resourceOwnerObject->getProfileUrl($token);
                if (!$profileUrl) {
                    //TODO: Add information about this if it happens
                    return $app->json($token, 201);
                }
                $profile = new SocialProfile($user->getId(), $profileUrl, $resourceOwner);

                /* @var $ghostUserManager GhostUserManager */
                $ghostUserManager = $app['users.ghostuser.manager'];
                if ($ghostUser = $ghostUserManager->getBySocialProfile($profile)) {
                    /* @var $userManager UserManager */
                    $userManager = $app['users.manager'];
                    $userManager->fuseUsers($user->getId(), $ghostUser->getId());
                    $ghostUserManager->saveAsUser($user->getId());
                } else {
                    /** @var $socialProfilesManager SocialProfileManager */
                    $socialProfilesManager = $app['users.socialprofile.manager'];
                    $socialProfilesManager->addSocialProfile($profile);
                }
            }
        }

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

        /* @var $authService AuthService */
        $authService = $app['auth.service'];
        $jwt = $authService->getToken($data['userId']);

        return $app->json(
            array(
                'user' => $user,
                'jwt' => $jwt,
            ),
            200
        );
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
        $tag = $request->get('tag', array());
        $type = $request->get('type', array());

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $user->getId());

        if ($commonWithId) {
            $filters['commonWithId'] = (int)$commonWithId;
        }

        foreach ($tag as $singleTag) {
            if (!empty($singleTag)) {
                $filters['tag'][] = urldecode($singleTag);
            }
        }

        foreach ($type as $singleType) {
            if (!empty($singleType)) {
                $filters['type'][] = urldecode($singleType);
            }
        }

        /* @var $model ContentPaginatedModel */
        $model = $app['users.content.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
            $result['totals'] = $model->countAll($user->getId());
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
        $tag = $request->get('tag', array());
        $type = $request->get('type', array());
        $showOnlyCommon = $request->get('showOnlyCommon', 0);

        if (null === $otherUserId) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $otherUserId, 'id2' => $user->getId(), 'showOnlyCommon' => (int)$showOnlyCommon);

        foreach ($tag as $singleTag) {
            if (!empty($singleTag)) {
                $filters['tag'][] = urldecode($singleTag);
            }
        }

        foreach ($type as $singleType) {
            if (!empty($singleType)) {
                $filters['type'][] = urldecode($singleType);
            }
        }

        /* @var $model \Model\User\ContentComparePaginatedModel */
        $model = $app['users.content.compare.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
            $result['totals'] = $model->countAll($otherUserId, $user->getId(), $showOnlyCommon);
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

        $originContext = isset($data['originContext']) ? $data['originContext'] : null;
        $originName = isset($data['originName']) ? $data['originName'] : null;
        try {
            /* @var RateModel $model */
            $model = $app['users.rate.model'];
            $result = $model->userRateLink($user->getId(), $data['id'], 'nekuno', null, $rate, true, $originContext, $originName);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getContentAllTagsAction(Request $request, Application $app)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if ($search) {
            $search = urldecode($search);
        }

        /* @var $model \Model\User\Recommendation\ContentRecommendationTagModel */
        $model = $app['users.recommendation.content.tag.model'];

        try {
            $result = $model->getAllTags($search, $limit);
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
        $filters['userFilters'] = $profileFilterModel->getFilters($locale);

        //user-dependent filters

        /* @var $userFilterModel User\UserFilterModel */
        $userFilterModel = $app['users.userFilter.model'];
        $userFilters = $userFilterModel->getFilters($locale);

        //TODO: Move this logic to userFilter during/after QS-982 (remove filter logic from GroupModel)
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

        $filters['userFilters'] += $userFilters;

        // content filters

        /* @var $contentFilterModel User\ContentFilterModel */
        $contentFilterModel = $app['users.contentFilter.model'];
        $filters['contentFilters'] = $contentFilterModel->getFilters($locale);

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
