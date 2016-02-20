<?php

namespace Controller\User;

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
class TokensController
{

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getAllAction(Request $request, Application $app)
    {
        // TODO: Change with $this->getUserId() and remove Request from parameters
        $id = $request->request->get('userId');

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $tokens = $model->getAll($id);

        return $app->json($tokens);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function getAction(Request $request, Application $app, $resourceOwner)
    {
        // TODO: Change with $this->getUserId() and remove Request from parameters
        $id = $request->request->get('userId');

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
        // TODO: Change with $this->getUserId()
        $id = $request->request->get('userId');
        $request->request->remove('userId');

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->create($id, $resourceOwner, $request->request->all());

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
            $profile = new SocialProfile($id, $profileUrl, $resourceOwner);

            /* @var $ghostUserManager GhostUserManager */
            $ghostUserManager = $app['users.ghostuser.manager'];
            if ($ghostUser = $ghostUserManager->getBySocialProfile($profile)) {
                /* @var $userManager UserManager */
                $userManager = $app['users.model'];
                $userManager->fuseUsers($id, $ghostUser->getId());
                $ghostUserManager->saveAsUser($id);
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
        // TODO: Change with $this->getUserId()
        $id = $request->request->get('userId');

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->update($id, $resourceOwner, $request->request->all());

        return $app->json($token);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function deleteAction(Request $request, Application $app, $resourceOwner)
    {
        // TODO: Change with $this->getUserId() and remove Request from parameters
        $id = $request->request->get('userId');

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->remove($id, $resourceOwner);

        return $app->json($token);
    }

}
