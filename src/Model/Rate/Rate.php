<?php

namespace Model\Rate;

class Rate implements \JsonSerializable
{
    protected $id;
    protected $resources;
    protected $timestamp;
    protected $linkUrl;
    protected $userId;
    protected $originContext;
    protected $originName;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param mixed $resources
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return mixed
     */
    public function getLinkUrl()
    {
        return $this->linkUrl;
    }

    /**
     * @param mixed $linkUrl
     */
    public function setLinkUrl($linkUrl)
    {
        $this->linkUrl = $linkUrl;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getOriginContext()
    {
        return $this->originContext;
    }

    /**
     * @param mixed $originContext
     */
    public function setOriginContext($originContext)
    {
        $this->originContext = $originContext;
    }

    /**
     * @return mixed
     */
    public function getOriginName()
    {
        return $this->originName;
    }

    /**
     * @param mixed $originName
     */
    public function setOriginName($originName)
    {
        $this->originName = $originName;
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'resources' => $this->resources,
            'timestamp' => $this->timestamp,
            'linkUrl' => $this->linkUrl,
            'userId' => $this->userId,
            'originContext' => $this->originContext,
            'originName' => $this->originName,
        );
    }

}