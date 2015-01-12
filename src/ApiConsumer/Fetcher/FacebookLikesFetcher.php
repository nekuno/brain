<?php

namespace ApiConsumer\Fetcher;


class FacebookLikesFetcher extends AbstractFacebookFetcher
{
    public function getUrl()
    {
        return parent::getUrl() . '/likes';
    }

    protected function getQuery()
    {
        return array_merge(
            array('fields' => 'link,website'),
            parent::getQuery()
        );
    }
}