<?php

namespace ApiConsumer\Fetcher;


class FacebookLinksFetcher extends AbstractFacebookFetcher
{
    public function getUrl()
    {
        return parent::getUrl() . '/links';
    }

    protected function getQuery()
    {
        return array_merge(
            array('fields' => 'link,created_time'),
            parent::getQuery()
        );
    }
}