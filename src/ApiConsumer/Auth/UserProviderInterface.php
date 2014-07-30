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
     * @param $acessToken
     * @param $creationTime
     * @param $expirationTime
     */
	public function updateAccessToken($resource, $userId, $acessToken, $creationTime, $expirationTime);
}
