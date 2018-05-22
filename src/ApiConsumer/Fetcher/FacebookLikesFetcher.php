<?php

namespace ApiConsumer\Fetcher;


use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;

class FacebookLikesFetcher extends AbstractFacebookFetcher
{
    public function getUrl($userId = null)
    {
        return parent::getUrl($userId) . '/likes';
    }

    protected function getQuery($paginationId = null)
    {
        return array_merge(
            array('fields' => 'id,link,website,created_time'),
            parent::getQuery($paginationId)
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
            if (null == $preprocessedLink->getType()){
                $preprocessedLink->setType(FacebookUrlParser::FACEBOOK_PAGE);
            }
        }

        return $parsed;
    }
}