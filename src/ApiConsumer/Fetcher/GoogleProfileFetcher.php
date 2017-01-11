<?php

namespace ApiConsumer\Fetcher;


use ApiConsumer\LinkProcessor\PreprocessedLink;

class GoogleProfileFetcher extends AbstractFetcher{


    /**
     * {@inheritDoc}
     */
    public function fetchLinksFromUserFeed($user, $public)
    {
        $this->setUser($user);
        $response = $this->resourceOwner->authorizedApiRequest($this->getUrl(), $this->getQuery(), $this->user);

        $preprocessedLink = new PreprocessedLink($response['url']);
        $link = array('resource' => $this->resourceOwner->getName());
        $preprocessedLink->setLink($link);
        return array($preprocessedLink);
    }

    public function getUrl()
    {
        return 'plus/v1/people/' . $this->user['googleID'];
    }

    public function getResourceOwner()
    {
        return $this->resourceOwner;
    }


}