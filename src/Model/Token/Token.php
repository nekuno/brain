<?php

namespace Model\Token;

class Token implements \JsonSerializable
{
    protected $userId;

    protected $resourceOwner;

    protected $resourceId;

    protected $oauthToken;

    protected $oauthTokenSecret;

    protected $createdTime;

    protected $updatedTime;

    protected $expireTime = 0;

    protected $refreshToken;

    public function __construct($data = array())
    {
        if (isset($data['oauthToken'])) {
            $this->setOauthToken($data['oauthToken']);
        }
        $this->setResourceOwner($data['resourceOwner']);
        $this->setResourceId($data['resourceId']);

        if (isset($data['oauthTokenSecret'])) {
            $this->setOauthTokenSecret($data['oauthTokenSecret']);
        }
        if (isset($data['expireTime'])) {
            $this->setExpireTime($data['expireTime']);
        }
        if (isset($data['refreshToken'])) {
            $this->setRefreshToken($data['refreshToken']);
        }
        if (isset($data['createdTime'])) {
            $this->setCreatedTime($data['createdTime']);
        }
        if (isset($data['updatedTime'])) {
            $this->setUpdatedTime($data['updatedTime']);
        }
    }

    public function toArray()
    {
        $array = array();
        foreach ($this as $attribute => $value) {
            if (null !== $value) {
                $array[$attribute] = $value;
            }
        }

        return $array;
    }

    function jsonSerialize()
    {
        return $this->toArray();
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
    public function getResourceOwner()
    {
        return $this->resourceOwner;
    }

    /**
     * @param mixed $resourceOwner
     */
    public function setResourceOwner($resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    /**
     * @return mixed
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param mixed $resourceId
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @return mixed
     */
    public function getOauthToken()
    {
        return $this->oauthToken;
    }

    /**
     * @param mixed $oauthToken
     */
    public function setOauthToken($oauthToken)
    {
        $this->oauthToken = $oauthToken;
    }

    /**
     * @return mixed
     */
    public function getOauthTokenSecret()
    {
        return $this->oauthTokenSecret;
    }

    /**
     * @param mixed $oauthTokenSecret
     */
    public function setOauthTokenSecret($oauthTokenSecret)
    {
        $this->oauthTokenSecret = $oauthTokenSecret;
    }

    /**
     * @return mixed
     */
    public function getCreatedTime()
    {
        return $this->createdTime;
    }

    /**
     * @param mixed $createdTime
     */
    public function setCreatedTime($createdTime)
    {
        $this->createdTime = $createdTime;
    }

    /**
     * @return mixed
     */
    public function getUpdatedTime()
    {
        return $this->updatedTime;
    }

    /**
     * @param mixed $updatedTime
     */
    public function setUpdatedTime($updatedTime)
    {
        $this->updatedTime = $updatedTime;
    }

    /**
     * @return mixed
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * @param mixed $expireTime
     */
    public function setExpireTime($expireTime)
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param mixed $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }
}