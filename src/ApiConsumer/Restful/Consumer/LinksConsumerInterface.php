<?php

namespace ApiConsumer\Restful\Consumer;

interface LinksConsumerInterface
{

    /**
     * Fetch links from user feed
     *
     * @param $userId
     * @return mixed
     */
    public function fetchLinksFromUserFeed($userId);
}
