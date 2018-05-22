<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;

class TwitterIntentProcessor extends AbstractTwitterProcessor
{

    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        try {
            $profileUrl = $this->buildProfileUrl($preprocessedLink);
        } catch (UrlNotValidException $e) {
            throw new CannotProcessException($e->getUrl(), 'Getting userId from a twitter intent url');
        }

        throw new UrlChangedException($preprocessedLink->getUrl(), $profileUrl);
    }

    protected function getUserId(PreprocessedLink $preprocessedLink)
    {
        return !empty($this->getUserIdFromResourceId($preprocessedLink)) ? $this->getUserIdFromResourceId($preprocessedLink) : $this->parser->getProfileId($preprocessedLink->getUrl());
    }

    protected function getUserIdFromResourceId(PreprocessedLink $preprocessedLink)
    {
        if ($resourceId = $preprocessedLink->getResourceItemId()) {
            return array('id' => $resourceId);
        }

        return array();
    }

    protected function buildProfileUrl(PreprocessedLink $preprocessedLink)
    {
        $userId = $this->getUserId($preprocessedLink);

        if (isset($userId['screen_name'])) {
            return $this->parser->buildUserUrl($userId['screen_name']);
        }

        if (isset($userId['user_id'])) {
            $token = $preprocessedLink->getToken();
            $users = $this->resourceOwner->lookupUsersBy('user_id', array($userId['user_id']), $token);
            $links = $this->resourceOwner->buildProfilesFromLookup($users);

            $link = reset($links);
            return $link->getUrl();
        }

        return null;
    }
}