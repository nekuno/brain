<?php

namespace ApiConsumer\Fetcher;


use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;

class FacebookLikesFetcher extends AbstractFacebookFetcher
{
    public function getUrl()
    {
        return parent::getUrl() . '/likes';
    }

    protected function getQuery()
    {
        return array_merge(
            array('fields' => 'id,link,website,created_time'),
            parent::getQuery()
        );
    }


    /**
     * {@inheritDoc}
     */
    protected function parseLinks(array $rawFeed)
    {
        /** @var PreprocessedLink[] $parsed */
        $parsed = parent::parseLinks($rawFeed);

        // this endpoint only gives back Facebook Page likes
        foreach ($parsed as $preprocessedLink) {
            $preprocessedLink->setType(FacebookUrlParser::FACEBOOK_PAGE);
        }

        return $parsed;
    }
}