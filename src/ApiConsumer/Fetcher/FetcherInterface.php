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
     * @return mixed
     */
    public function fetchLinksFromUserFeed($user);
}
