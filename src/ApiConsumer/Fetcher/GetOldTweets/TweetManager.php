<?php

namespace ApiConsumer\Fetcher\GetOldTweets;


class TweetManager extends \Manager\TweetManager
{

    /**
     * {@inheritDoc}
     */
    public function getUrlResponse($username, $since, $until, $querySearch, $scrollCursor)
    {
        sleep(rand(1,2));
        //See https://github.com/guzzle/guzzle/pull/879 or newer for charset setting
        $response = parent::getUrlResponse($username, $since, $until, $querySearch, $scrollCursor);
        $response['items_html'] = utf8_decode($response['items_html']);
        return $response;
    }

}