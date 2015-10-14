<?php

namespace ApiConsumer\Fetcher;

interface FetcherInterface
{

    /**
     * Fetch links from user feed
     *
     * @param $user
     * @param boolean $public
     * @return array
     */
    public function fetchLinksFromUserFeed($user, $public);
}