<?php

namespace Model\Content;

class Interest implements \JsonSerializable
{
    protected $id;

    protected $url;

    protected $title;

    protected $description;

    protected $thumbnail;

    protected $synonymous = array();

    protected $tags = array();

    protected $types = array();

    protected $user_rates = array();

    protected $embed = array();

    protected $processed = 1;

    public static function buildFromLinkArray(array $array)
    {
        $interest = new static();

        if (isset($array['id'])){
            $interest->setId($array['id']);
        }
        if (isset($array['url'])){
            $interest->setUrl($array['url']);
        }
        if (isset($array['title'])){
            $interest->setTitle($array['title']);
        }
        if (isset($array['description'])){
            $interest->setDescription($array['description']);
        }
        if (isset($array['tags'])) {
            $interest->setTags($array['tags']);
        }
        if (isset($array['thumbnail'])){
            $interest->setThumbnail($array['thumbnail']);
        }
        if (isset($array['embed_type']) && $array['embed_type'] != null){
            $interest->setEmbed(array(
                'type' => $array['embed_type'],
                'id' => $array['embed_id']
            ));
        }
        if (isset($array['additionalLabels']) && is_array($array['additionalLabels'])){
            foreach ($array['additionalLabels'] as $type){
                $interest->addType($type);
            }
        }
        if (isset($array['processed'])) {
            $interest->setProcessed($array['processed']);
        }

        return $interest;
    }

    public function jsonSerialize()
    {
        $interest = array(
            'id' => $this->getId(),
            'contentId' => $this->getId(),
            'url' => $this->getUrl(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'thumbnail' => $this->getThumbnail(),
            'synonymous' => $this->getSynonymous(),
            'tags' => $this->getTags(),
            'types' => $this->getTypes(),
            'user_rates' => $this->getUserRates(),
            'processed' => $this->getProcessed(),
        );

        if (!empty($this->getEmbed())){
            $interest['embed'] = $this->getEmbed();
        }

        return $interest;
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

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
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
     * @return array
     */
    public function getTypes()
    {
        if (!in_array('Link', $this->types)){
            $this->addType('Link');
        }

        return $this->types;
    }

    /**
     * @param array $types
     */
    public function setTypes($types)
    {
        $this->types = $types;
    }

    public function addType($type)
    {
        $this->types[] = $type;
    }

    /**
     * @return array
     */
    public function getUserRates()
    {
        return $this->user_rates;
    }

    /**
     * @param array $user_rates
     */
    public function setUserRates($user_rates)
    {
        $this->user_rates = $user_rates;
    }

    public function addUserRate($user_rate)
    {
        $this->user_rates[] = $user_rate;
    }

    /**
     * @return array
     */
    public function getEmbed()
    {
        return $this->embed;
    }

    /**
     * @param array $embed
     */
    public function setEmbed($embed)
    {
        $this->embed = $embed;
    }

    /**
     * @return int
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * @param int $processed
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }


}