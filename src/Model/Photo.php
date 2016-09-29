<?php

namespace Model;

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
        return file_exists($this->getFullPath()) ? $this->host . $this->getPath() : $this->host . $this->getDefaultPath();
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
            $thumbnail[$size] = file_exists($this->base . $cache . $this->getPath()) ? $this->host . $cache . $this->getPath() : $this->host . $resolve . $this->getPath();
        }

        return array(
            'id' => $this->getId(),
            'createdAt' => $this->getCreatedAt(),
            'url' => $this->getUrl(),
            'thumbnail' => $thumbnail,
        );
    }

}