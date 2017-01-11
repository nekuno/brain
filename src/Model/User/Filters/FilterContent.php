<?php

namespace Model\User\Filters;


class FilterContent implements \JsonSerializable
{
    protected $tag = array();

    protected $type;

    protected $id;

    /**
     * FilterContent constructor.
     * @param array $type
     */
    public function __construct($type = array('Link'))
    {
        $this->type = $type;
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
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param mixed $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
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
        $filters = array(
            'type' => $this->getType(),
            'tags' => $this->getTag(),
        );
        if (empty($filters['tags'])) {
            unset ($filters['tags']);
        }
        if (empty($filters['type']) || $filters['type'] === array('Link')){
            unset($filters['type']);
        }
        if (empty($filters)){
            $filters = new \StdClass();
        }
        return array(
            'id' => $this->getId(),
            'contentFilters' => $filters,
        );
    }
}