<?php

namespace Model;


class Link implements \JsonSerializable
{
    protected $id;
    protected $url;
    protected $title;
    protected $thumbnail;
    protected $description;
    protected $tags = array();
    protected $created;
    protected $processed = true;
    protected $language;
    /** @var Link[] */
    protected $synonymous = array();

    public static function buildFromArray(array $array){

        $link = new static();

        if (isset($array['id'])){
            $link->setId($array['id']);
        }
        if (isset($array['url'])){
            $link->setUrl($array['url']);
        }
        if (isset($array['title'])){
            $link->setTitle($array['title']);
        }
        if (isset($array['description'])){
            $link->setDescription($array['description']);
        }
        if (isset($array['tags'])) {
            $link->setTags($array['tags']);
        }
        if (isset($array['thumbnail'])){
            $link->setThumbnail($array['thumbnail']);
        }
        if (isset($array['processed'])){
            $link->setProcessed((boolean)$array['processed']);
        }
        if (isset($array['timestamp'])){
            $link->setCreated($array['timestamp']);
        }

        return $link;
    }

    public function toArray() {
        $array = array();
        foreach ($this as $attribute => $value) {
            $array[$attribute] = $value;
        }

        return $array;
    }

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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param mixed $thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return mixed
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * @param mixed $processed
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return array
     */
    public function getSynonymous()
    {
        return $this->synonymous;
    }

    /**
     * @param array $synonymous
     */
    public function setSynonymous($synonymous)
    {
        $this->synonymous = $synonymous;
    }

    public function addSynonymous($synonymous)
    {
        $this->synonymous[] = $synonymous;
    }

    public function isComplete() {
        return $this->getUrl() && $this->getTitle() && $this->getThumbnail();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return $this->toArray();
    }
}