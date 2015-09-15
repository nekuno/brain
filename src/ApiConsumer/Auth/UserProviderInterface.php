<?php

namespace ApiConsumer\Auth;

interface UserProviderInterface
{

    /**
     * Get users by resource owner
     *
     * @param $resource
     * @param $userId
     * @return mixed
     */
    public function getUsersByResource($resource, $userId = null);

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
