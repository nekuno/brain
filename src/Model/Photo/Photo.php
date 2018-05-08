<?php

namespace Model\Photo;

abstract class Photo implements \JsonSerializable
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
     * @var integer
     */
    protected $userId;

    /**
     * @var boolean
     */
    protected $isProfilePhoto;

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
     * Returns an array of sizes, with this shape:
     * array('my_size' => array('cache' => 'path_to_cache_my_size', 'resolve' => 'path_to_resolve_my_size'));
     *
     * @return array
     */
    abstract protected function getSizes();

    /**
     * Returns default image path
     *
     * @return string
     */
    abstract protected function getDefaultPath();

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
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param integer $userId
     * @return Photo
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    public function getIsProfilePhoto()
    {
        return $this->isProfilePhoto;
    }

    public function setIsProfilePhoto($isProfilePhoto)
    {
        $this->isProfilePhoto = $isProfilePhoto;
    }

    public function getFullPath()
    {
        return $this->base . $this->getPath();
    }

    public function getUrl()
    {
        return is_file($this->getFullPath()) ? $this->host . $this->getPath() : $this->host . $this->getDefaultPath();
    }

    public function getExtension()
    {
        $fileName = basename($this->path);

        return strrpos($fileName, '.') !== false ? substr($fileName, strrpos($fileName, '.')) : '';
    }

    public function delete()
    {

        if (is_writable($this->getFullPath())) {
            unlink($this->getFullPath());
        }

        foreach ($this->getSizes() as $size => $sizePaths) {
            $cache = $this->base . $sizePaths['cache'] . $this->getPath();
            if (is_writable($cache)) {
                unlink($cache);
            }
        }
    }

    public function jsonSerialize()
    {
        $thumbnail = array();
        foreach ($this->getSizes() as $size => $sizePaths) {
            $cache = $sizePaths['cache'];
            $resolve = $sizePaths['resolve'];
            $path = $this->getPath() ? $this->getPath() : $this->getDefaultPath();
            $thumbnail[$size] = is_file($this->base . $cache . $path) ? $this->host . $cache . $path . '?v=' . md5_file($this->base . $cache . $path) : $this->host . $resolve . $path . '?v=' . time();
        }

        return array(
            'id' => $this->getId(),
            'createdAt' => $this->getCreatedAt(),
            'url' => $this->getUrl(),
            'thumbnail' => $thumbnail,
            'isProfilePhoto' => $this->getIsProfilePhoto(),
        );
    }

}