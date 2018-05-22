<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Token\Token;

interface FetcherInterface
{

    /**
     * Fetch links using user authorization
     *
     * @param $token
     * @return PreprocessedLink[]
     */
    public function fetchLinksFromUserFeed(Token $token);

    /**
     * Fetch links using client authorization
     * @param string $username
     * @return PreprocessedLink[]
     */
    public function fetchAsClient($username);
}