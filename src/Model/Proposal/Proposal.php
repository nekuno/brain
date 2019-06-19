<?php

namespace Model\Proposal;

use Model\Filters\FilterUsers;
use Model\Profile\ProfileFields\AbstractField;

class Proposal implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string[]
     */
    protected $matches = [];

    /** @var AbstractField[] */
    protected $fields = array();

    /** @var FilterUsers */
    protected $filters;

    protected $hasMatch = false;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var string
     */
    protected $host;

    /**
     * Proposal constructor.
     * @param $type
     * @param AbstractField[] $fields
     */
    public function __construct($type, array $fields, $base, $host)
    {
        $this->type = $type;
        $this->fields = $fields;
        $this->base = $base;
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return ucfirst($this->type);
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return AbstractField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param AbstractField[] $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function addField(AbstractField $field)
    {
        $this->fields[] = $field;
    }

    /** @return AbstractField */
    public function getField($name)
    {
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    public function removeField($name)
    {
        foreach ($this->fields as $index => $field) {
            if ($field->getName() === $name) {
                unset($this->fields[$index]);
            }
        }

        return null;
    }

    /**
     * @return FilterUsers
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param FilterUsers $filters
     */
    public function setFilters(FilterUsers $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return string[]
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * @param array[] $matches
     */
    public function setMatches(array $matches): void
    {
        $this->matches = $matches;
    }

    public function countMatches()
    {
        return count($this->matches);
    }

    /**
     * @return bool
     */
    public function hasMatch(): bool
    {
        return $this->hasMatch;
    }

    /**
     * @param bool $hasMatch
     */
    public function setHasMatch(bool $hasMatch): void
    {
        $this->hasMatch = $hasMatch;
    }

    public function jsonSerialize()
    {
        $proposal = array(
            'id' => $this->getId(),
            'type' => $this->getType(),
            'filters' => $this->getFilters(),
            'matches' => $this->getMatches(),
            'countMatches' => $this->countMatches(),
            'hasMatch' => $this->hasMatch(),
            'fields' => array(),
        );

        foreach ($this->getFields() as $field) {
            $proposal['fields'][$field->getName()] = $field;
            if ($field->getName() === 'photo') {
                $proposal['fields'][$field->getName()] = $this->host . $field->getValue();
            }
        }

        return $proposal;
    }

}