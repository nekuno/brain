<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 29/10/15
 * Time: 10:20
 */

namespace ApiConsumer\Fetcher;


class GoogleProfileFetcher extends AbstractFetcher{


    /**
     * {@inheritDoc}
     */
    public function fetchLinksFromUserFeed($user, $public)
    {
        $this->setUser($user);
        $response = $this->resourceOwner->authorizedApiRequest($this->getUrl(), $this->getQuery(), $this->user);
        return array($response);
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