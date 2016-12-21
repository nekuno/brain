<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;

class TwitterIntentProcessor extends AbstractProcessor
{
    /** @var  TwitterUrlParser */
    protected $parser;

    /** @var  TwitterResourceOwner */
    protected $resourceOwner;

    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        try {
            $userId = $this->getUserId($preprocessedLink);
            $profileUrl = $this->buildProfileUrl($userId);
        } catch (UrlNotValidException $e) {
            throw new CannotProcessException($e->getUrl(), 'Getting userId from a twitter intent url');
        }

        throw new UrlChangedException($preprocessedLink->getUrl(), $profileUrl);
    }

    protected function getUserId(PreprocessedLink $preprocessedLink)
    {
        return !empty($this->getUserIdFromResourceId($preprocessedLink)) ? $this->getUserIdFromResourceId($preprocessedLink) : $this->parser->getProfileId($preprocessedLink->getUrl());
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    private function getUserIdFromResourceId(PreprocessedLink $preprocessedLink)
    {
        if ($resourceId = $preprocessedLink->getResourceItemId()) {
            return array('id' => $resourceId);
        }

        return array();
    }

    public function buildProfileUrl(array $userId)
    {
        if (isset($userId['screen_name'])) {
            return $this->parser->buildUserUrl($userId['screen_name']);
        }

        if (isset($userId['user_id'])) {
            $response = $this->resourceOwner->lookupUsersBy('user_id', array($userId['user_id']));
            $link = $this->resourceOwner->buildProfileFromLookup($response);

            return $link['url'];
        }

        return null;
    }
}