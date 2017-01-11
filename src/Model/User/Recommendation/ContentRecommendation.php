<?php

namespace Model\User\Recommendation;


class ContentRecommendation implements \JsonSerializable
{
    protected $content;
    protected $synonymous;
    protected $tags;
    protected $types;
    protected $embed;
    protected $match;
    protected $staticThumbnail;

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getSynonymous()
    {
        return $this->synonymous;
    }

    /**
     * @param mixed $synonymous
     */
    public function setSynonymous($synonymous)
    {
        $this->synonymous = $synonymous;
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

    /**
     * @return mixed
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param mixed $types
     */
    public function setTypes($types)
    {
        $this->types = $types;
    }

    /**
     * @return mixed
     */
    public function getEmbed()
    {
        return $this->embed;
    }

    /**
     * @param mixed $embed
     */
    public function setEmbed($embed)
    {
        $this->embed = $embed;
    }

    /**
     * @return mixed
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * @param mixed $match
     */
    public function setMatch($match)
    {
        $this->match = $match;
    }

    /**
     * @return mixed
     */
    public function getStaticThumbnail()
    {
        return $this->staticThumbnail;
    }

    /**
     * @param mixed $staticThumbnail
     */
    public function setStaticThumbnail($staticThumbnail)
    {
        $this->staticThumbnail = $staticThumbnail;
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
        return array(
          'content' => $this->getContent(),
          'synonymous' => $this->getSynonymous(),
          'tags' => $this->getTags(),
          'types' => $this->getTypes(),
          'embed' => $this->getEmbed(),
          'match' => $this->getMatch(),
          'staticThumbnail' => $this->getStaticThumbnail(),
        );
    }
}