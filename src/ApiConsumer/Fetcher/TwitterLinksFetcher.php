<?php

namespace ApiConsumer\Fetcher;

class TwitterLinksFetcher extends AbstractTweetsFetcher
{
    protected $url = 'statuses/user_timeline.json';

    protected function getQuery($paginationId = null)
    {
        return array_merge(
            parent::getQuery($paginationId),
            array(
                'count' => $this->pageLength,
                'exclude_replies' => 'false',
                'contributor_details' => 'false',
                'include_rts' => 'true',
            )
        );
    }
}