<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 28/06/14
 * Time: 18:33
 */

namespace ApiConsumer\Auth;


interface UserProviderInterface {

    /**
     * Get users by resource owner
     *
     * @param $resource
     * @param $userId
     * @return mixed
     */
    function getUsersByResource($resource, $userId = null);

} 