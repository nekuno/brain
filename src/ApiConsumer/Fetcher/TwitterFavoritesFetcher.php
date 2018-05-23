<?php

namespace ApiConsumer\Fetcher;

class TwitterFavoritesFetcher extends AbstractTweetsFetcher
{
    protected $url = 'favorites/list.json';

    protected function getQuery($paginationId = null)
    {
        return array_merge(
            parent::getQuery($paginationId),
            array(
                'count' => $this->pageLength,
                'include_entities' => 'true',
            )
        );
    }
}