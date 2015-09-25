<?php

namespace ApiConsumer\Auth;

interface UserProviderInterface
{

    /**
     * Update Oauth access token
     *
     * @param $resource
     * @param $userId
     * @param $accessToken
     * @param $creationTime
     * @param $expirationTime
     */
	public function updateAccessToken($resource, $userId, $accessToken, $creationTime, $expirationTime);

    /**
     * Update Oauth2 refresh token
     *
     * @param $refreshToken
     * @param $resource
     * @param $userId
     */
    public function updateRefreshToken($refreshToken, $resource, $userId);
}
