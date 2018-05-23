<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\ResourceOwner\TwitterResourceOwner;

abstract class AbstractTwitterFetcher extends BasicPaginationFetcher
{
    /** @var $resourceOwner TwitterResourceOwner */
    protected $resourceOwner;

    protected function getQuery($paginationId = null)
    {
        $usernameQuery = $this->username ? array('screen_name' => $this->username) : array();
        return array_merge(
            parent::getQuery($paginationId),
            $usernameQuery
        );
    }

}