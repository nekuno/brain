<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace ApiConsumer\Fetcher\GetOldTweets;


/**
 * {@inheritDoc}
 */
class TweetManager extends \Manager\TweetManager
{

    /**
     * {@inheritDoc}
     */
    public function getUrlResponse($username, $since, $until, $querySearch, $scrollCursor)
    {
        sleep(rand(1,2));
        return parent::getUrlResponse($username, $since, $until, $querySearch, $scrollCursor);
    }

}