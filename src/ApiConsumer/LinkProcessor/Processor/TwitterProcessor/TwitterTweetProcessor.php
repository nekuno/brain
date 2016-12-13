<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;

class TwitterTweetProcessor extends AbstractProcessor
{
    /**
     * @var TwitterUrlParser
     */
    protected $parser;

    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;

    public function requestItem(PreprocessedLink $preprocessedLink)
    {
        $statusId = $this->getItemId($preprocessedLink->getUrl());

        $url = $this->processTweetStatus($statusId);

        throw new UrlChangedException($preprocessedLink->getUrl(), $url);
    }

    /**
     * Follow embedded tweets (like from retweets) until last url
     * @param $statusId
     * @param $counter int Avoid infinite loops and some "joke" tweet chains
     * @return string|bool
     */
    private function processTweetStatus($statusId, $counter = 0)
    {
        if ($counter >= 10) {
            return false;
        }

        $apiResponse = $this->resourceOwner->requestStatus($statusId);

        $link = $this->extractLinkFromResponse($apiResponse);

        if (isset($link['id'])) {
            return $this->processTweetStatus($link['id'], ++$counter);
        }

        if (isset($link['url'])) {
            return $link['url'];
        }

        return false;
    }

    private function extractLinkFromResponse($apiResponse)
    {
        //if tweet quotes another
        if (isset($apiResponse['quoted_status_id'])) {
            //if tweet is main, API returns quoted_status
            if (isset($apiResponse['quoted_status'])) {

                return $this->extractLinkFromResponse($apiResponse['quoted_status']);

            } else if (isset($apiResponse['is_quote_status']) && $apiResponse['is_quote_status'] == true) {
                return array('id' => $apiResponse['quoted_status_id']);
            } else {
                //should not be able to enter here
            }
        }

        //if tweet includes url or media in text
        if (isset($apiResponse['entities'])) {
            $entities = $apiResponse['entities'];

            $media = $this->getEntityUrl($entities, 'media');
            if ($media) {
                return $media;
            }

            $url = $this->getEntityUrl($entities, 'urls');
            if ($url) {
                return $url;
            }
        }
        //we do not want tweets with no content
        return false;
    }

    private function getEntityUrl($entities, $name)
    {
        if (isset($entities[$name]) && !empty($entities[$name])) {
            $urlObject = $entities[$name][0]; //TODO: Foreach
            return array('url' => $urlObject['expanded_url']);
        }

        return false;
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    protected function getItemIdFromParser($url)
    {
        return $this->parser->getStatusId($url);
    }

}