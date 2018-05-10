<?php

namespace Model\Profile;

class Profile implements \JsonSerializable
{
    protected $id;

    protected $values = array();

    /**
     * FilterUsers constructor.
     * @param array $metadata
     */
    public function __construct(array $metadata)
    {
        foreach ($metadata as $key => $value) {
            $this->values[$key] = null;
        }
    }

    public function get($field)
    {
        if (!array_key_exists($field, $this->values)) {
            return null;
        }

        return $this->values[$field];
    }

    public function set($field, $value)
    {
        if (!array_key_exists($field, $this->values)) {
            return;
        }

        $this->values[$field] = $value;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getValues()
    {
        $values = $this->values;
        foreach ($values as $key => $value) {
            if (!isset($value)) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $idArray = array('id' => $this->getId());

        $values = $this->values;
        foreach ($values as $key => $value) {
            if (!isset($value)) {
                unset($values[$key]);
            }
        }

        return $idArray + $values;
    }

    public function toArray()
    {
        return $this->jsonSerialize();
    }
}