<?php

namespace Model\Link;

class Link implements \JsonSerializable
{
    const WEB_LABEL = 'Web';

    protected $id;
    protected $url;
    protected $title;
    protected $thumbnail;
    protected $thumbnailMedium;
    protected $thumbnailSmall;
    protected $description;
    protected $tags = array();
    protected $created;
    protected $processed = true;
    protected $lastChecked;
    protected $lastReprocessed;
    protected $reprocessedCount;
    protected $imageProcessed;
    protected $language;
    /** @var Link[] */
    protected $synonymous = array();
    protected $additionalLabels = array();

    public static function buildFromLink(Link $link)
    {
        $array = $link->toArray();

        /** @var Video $me */
        $me = self::buildFromArray($array);

        return $me;
    }
    public static function buildFromArray(array $array)
    {
        $link = new static();

        if (isset($array['id'])) {
            $link->setId($array['id']);
        }
        if (isset($array['url'])) {
            $link->setUrl($array['url']);
        }
        if (isset($array['title'])) {
            $link->setTitle($array['title']);
        }
        if (isset($array['description'])) {
            $link->setDescription($array['description']);
        }
        if (isset($array['tags'])) {
            $link->setTags($array['tags']);
        }
        if (isset($array['thumbnail'])) {
            $link->setThumbnail($array['thumbnail']);
        }
        if (isset($array['thumbnailSmall'])) {
            $link->setThumbnailSmall($array['thumbnailSmall']);
        }
        if (isset($array['thumbnailMedium'])) {
            $link->setThumbnailMedium($array['thumbnailMedium']);
        }
        if (isset($array['processed'])) {
            $link->setProcessed((boolean)$array['processed']);
        }
        if (isset($array['imageProcessed'])) {
            $link->setImageProcessed($array['imageProcessed']);
        }
        if (isset($array['timestamp'])) {
            $link->setCreated($array['timestamp']);
        }
        if (isset($array['additionalLabels'])) {
            $link->setAdditionalLabels($array['additionalLabels']);
        }

        return $link;
    }

    public function toArray()
    {
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
    public function getThumbnailLarge()
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
    public function getThumbnailMedium()
    {
        return $this->thumbnailMedium;
    }

    /**
     * @param mixed $thumbnailMedium
     */
    public function setThumbnailMedium($thumbnailMedium)
    {
        $this->thumbnailMedium = $thumbnailMedium;
    }

    /**
     * @return mixed
     */
    public function getThumbnailSmall()
    {
        return $this->thumbnailSmall;
    }

    /**
     * @param mixed $thumbnailSmall
     */
    public function setThumbnailSmall($thumbnailSmall)
    {
        $this->thumbnailSmall = $thumbnailSmall;
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
    public function getLastChecked()
    {
        return $this->lastChecked;
    }

    /**
     * @param mixed $lastChecked
     */
    public function setLastChecked($lastChecked)
    {
        $this->lastChecked = $lastChecked;
    }

    /**
     * @return mixed
     */
    public function getLastReprocessed()
    {
        return $this->lastReprocessed;
    }

    /**
     * @param mixed $lastReprocessed
     */
    public function setLastReprocessed($lastReprocessed)
    {
        $this->lastReprocessed = $lastReprocessed;
    }

    /**
     * @return mixed
     */
    public function getReprocessedCount()
    {
        return $this->reprocessedCount;
    }

    /**
     * @param mixed $reprocessedCount
     */
    public function setReprocessedCount($reprocessedCount)
    {
        $this->reprocessedCount = $reprocessedCount;
    }

    /**
     * @return mixed
     */
    public function getImageProcessed()
    {
        return $this->imageProcessed;
    }

    /**
     * @param mixed $imageProcessed
     */
    public function setImageProcessed($imageProcessed)
    {
        $this->imageProcessed = $imageProcessed;
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
     * @return Link[]
     */
    public function getSynonymous()
    {
        return $this->synonymous;
    }

    /**
     * @param Link[] $synonymous
     */
    public function setSynonymous($synonymous)
    {
        $this->synonymous = $synonymous;
    }

    public function addSynonymous(Link $synonymous)
    {
        $this->synonymous[] = $synonymous;
    }

    /**
     * @return array
     */
    public function getAdditionalLabels()
    {
        return $this->additionalLabels;
    }

    /**
     * @param array $additionalLabels
     */
    public function setAdditionalLabels($additionalLabels)
    {
        $this->additionalLabels = $additionalLabels;
    }

    /**
     * @param string $additionalLabel
     */
    public function addAdditionalLabels($additionalLabel)
    {
        $this->additionalLabels[] = $additionalLabel;
    }

    public function isComplete()
    {
        return $this->getUrl() && ($this->getTitle() || $this->getThumbnailLarge());
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