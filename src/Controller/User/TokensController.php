<?php

namespace Controller\User;

use Controller\BaseController;
use Http\OAuth\Factory\ResourceOwnerFactory;
use Http\OAuth\ResourceOwner\FacebookResourceOwner;
use Model\User\GhostUser\GhostUserManager;
use Model\User\SocialNetwork\SocialProfile;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\TokensModel;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TokensController
 * @package Controller
 */
class TokensController extends BaseController
{
    /**
     * @param integer $id
     * @param Application $app
     * @return JsonResponse
     */
    public function getAllAction(Application $app, $id)
    {
        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $tokens = $model->getAll($id);

        return $app->json($tokens);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function getAction(Application $app, $id, $resourceOwner)
    {
        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->getById($id, $resourceOwner);

        return $app->json($token);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function postAction(Request $request, Application $app, $resourceOwner)
    {
        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->create($this->getUserId(), $resourceOwner, $request->request->all());

        /* @var $resourceOwnerFactory ResourceOwnerFactory */
        $resourceOwnerFactory = $app['api_consumer.resource_owner_factory'];

        if ($resourceOwner === TokensModel::FACEBOOK) {

            /* @var $facebookResourceOwner FacebookResourceOwner */
            $facebookResourceOwner = $resourceOwnerFactory->build(TokensModel::FACEBOOK);

            if ($request->query->has('extend')) {
                $token = $facebookResourceOwner->extend($token);
            }

            if (array_key_exists('refreshToken', $token) && is_null($token['refreshToken'])) {
                $token = $facebookResourceOwner->forceRefreshAccessToken($token);
            }
        }

        if ($resourceOwner == TokensModel::TWITTER) {
            $resourceOwnerObject = $resourceOwnerFactory->build($resourceOwner);
            $profileUrl = $resourceOwnerObject->getProfileUrl($token);
            if (!$profileUrl) {
                //TODO: Add information about this if it happens
                return $app->json($token, 201);
            }
            $profile = new SocialProfile($this->getUserId(), $profileUrl, $resourceOwner);

            /* @var $ghostUserManager GhostUserManager */
            $ghostUserManager = $app['users.ghostuser.manager'];
            if ($ghostUser = $ghostUserManager->getBySocialProfile($profile)) {
                /* @var $userManager UserManager */
                $userManager = $app['users.manager'];
                $userManager->fuseUsers($this->getUserId(), $ghostUser->getId());
                $ghostUserManager->saveAsUser($this->getUserId());
            } else {
                /** @var $socialProfilesManager SocialProfileManager */
                $socialProfilesManager = $app['users.socialprofile.manager'];
                $socialProfilesManager->addSocialProfile($profile);
            }
        }

        return $app->json($token, 201);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function putAction(Request $request, Application $app, $resourceOwner)
    {
        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->update($this->getUserId(), $resourceOwner, $request->request->all());

        return $app->json($token);
    }

    /**
     * @param Application $app
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function deleteAction(Application $app, $resourceOwner)
    {
        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->remove($this->getUserId(), $resourceOwner);

        return $app->json($token);
    }
}
