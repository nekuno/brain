<?php

namespace ApiConsumer\Fetcher;


use ApiConsumer\LinkProcessor\PreprocessedLink;

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

        foreach ($parsed as $preprocessedLink) {
            $preprocessedLink->addToLink(array('pageId' => $preprocessedLink->getLink()['resourceItemId']));
        }

        return $parsed;
    }
}