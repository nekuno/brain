<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
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

        $apiResponse = $this->resourceOwner->requestStatus($statusId);

        $link = $this->extractLinkFromResponse($apiResponse);
        if (isset($link['url'])) {
            throw new UrlChangedException($preprocessedLink->getUrl(), $link['url']);
        } else {
            throw new CannotProcessException($preprocessedLink->getUrl(), 'We do not want tweets without url content');
        }
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

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $profileAvatar = null;
        if (isset($data['user'])){
            $profileAvatar = isset($data['user']['profile_image_url_https']) ? $data['user']['profile_image_url_https'] : ( isset($data['user']['profile_image_url']) ? $data['user']['profile_image_url'] : null);
        }

        return array($profileAvatar);
    }

    protected function getItemIdFromParser($url)
    {
        return $this->parser->getStatusId($url);
    }

}