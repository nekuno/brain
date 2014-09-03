<?php

namespace ApiConsumer\Fetcher;

interface FetcherInterface
{
    /**
     * Get ResourceOwner Name
     *
     * @return string
     */
    public function getResourceOwnerName();

    /**
     * Fetch links from user feed
     *
     * @param $user
     * @return array
     */
    public function fetchLinksFromUserFeed($user);
}