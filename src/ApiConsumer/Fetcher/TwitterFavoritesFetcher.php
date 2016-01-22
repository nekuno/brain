<?php

namespace ApiConsumer\Fetcher;

class TwitterFavoritesFetcher extends AbstractTweetsFetcher
{
    protected $url = 'favorites/list.json';

    protected function getQuery()
    {

        return array(
            'count' => $this->pageLength,
            'include_entities' => 'true',
        );
    }
}