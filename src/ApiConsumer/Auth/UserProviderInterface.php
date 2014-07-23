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

}
