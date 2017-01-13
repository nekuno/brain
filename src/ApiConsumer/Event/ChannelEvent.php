<?php


namespace ApiConsumer\Event;


use Symfony\Component\EventDispatcher\Event;

class ChannelEvent extends Event
{
    protected $resourceOwner;
    protected $channelUrl;
    protected $username;

    public function __construct($resourceOwner, $channelUrl = null, $username = null)
    {
        $this->resourceOwner = $resourceOwner;
        $this->channelUrl = $channelUrl;
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getResourceOwner()
    {
        return $this->resourceOwner;
    }

    /**
     * @param mixed $resourceOwner
     * @return ChannelEvent
     */
    public function setResourceOwner($resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannelUrl()
    {
        return $this->channelUrl;
    }

    /**
     * @param mixed $channelUrl
     * @return ChannelEvent
     */
    public function setChannelUrl($channelUrl)
    {
        $this->channelUrl = $channelUrl;
        return $this;
    }

    /**
     * @return null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param null $username
     * @return ChannelEvent
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

}