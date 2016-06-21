<?php

namespace Model;

class Photo implements \JsonSerializable
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var string
     */
    protected $host;

    public function __construct($base, $host)
    {
        $this->base = $base;
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Photo
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Photo
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return Photo
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Photo
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function getFullPath()
    {
        return $this->base . $this->getPath();
    }

    public function getUrl()
    {
        return $this->host . $this->getPath();
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'createdAt' => $this->getCreatedAt(),
            'url' => $this->getUrl(),
        );
    }

}