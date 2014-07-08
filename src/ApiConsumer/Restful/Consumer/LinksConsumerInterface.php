<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/27/14
 * Time: 11:57 AM
 */

namespace ApiConsumer\Restful\Consumer;


interface LinksConsumerInterface {

    /**
     * Fetch links from user feed
     *
     * @param $userId
     * @return mixed
     */
    public function fetchLinks($userId);

} 