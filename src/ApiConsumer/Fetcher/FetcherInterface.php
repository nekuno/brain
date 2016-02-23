<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;

interface FetcherInterface
{

    /**
     * Fetch links from user feed
     *
     * @param $user
     * @param boolean $public
     * @return PreprocessedLink[]
     */
    public function fetchLinksFromUserFeed($user, $public);
}