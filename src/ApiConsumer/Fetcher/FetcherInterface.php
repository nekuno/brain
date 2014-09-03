<?php

namespace ApiConsumer\Fetcher;

interface FetcherInterface
{

    /**
     * Fetch links from user feed
     *
     * @param $user
     * @return array
     */
    public function fetchLinksFromUserFeed($user);
}