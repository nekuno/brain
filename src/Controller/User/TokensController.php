<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Controller\User;


use Http\OAuth\Factory\ResourceOwnerFactory;
use Http\OAuth\ResourceOwner\FacebookResourceOwner;
use Manager\UserManager;
use Model\User;
use Model\User\GhostUser\GhostUserManager;
use Model\User\SocialNetwork\SocialProfile;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\TokensModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class TokensController
{
    public function postAction(Request $request, Application $app, User $user, $resourceOwner)
    {

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->create($user->getId(), $resourceOwner, $request->request->all());

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

        return $app->json($token, 201);
    }

}