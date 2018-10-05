<?php

namespace Model\Proposal;

use Model\Filters\FilterUsers;
use Model\Profile\ProfileFields\AbstractField;

class Proposal
{
    protected $id;

    protected $name;

    protected $matches = 0;

    /** @var AbstractField[] */
    protected $fields = array();

    /** @var FilterUsers */
    protected $filters;

    /**
     * Proposal constructor.
     * @param $name
     * @param AbstractField[] $fields
     */
    public function __construct($name, array $fields)
    {
        $this->name = $name;
        $this->fields = $fields;
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
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getLabel()
    {
        return ucfirst($this->name);
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
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

    public function getField($name)
    {
        foreach ($this->fields as $field)
        {
            if ($field->getName() === $name){
                return $field;
            }
        }

        return null;
    }

    public function removeField($name)
    {
        foreach ($this->fields as $index => $field)
        {
            if ($field->getName() === $name){
                unset($this->fields[$index]);
            }
        }

        return null;
    }

    /**
     * @return FilterUsers
     */
    public function getFilters(): FilterUsers
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
     * @return int
     */
    public function getMatches(): int
    {
        return $this->matches;
    }

    /**
     * @param int $matches
     */
    public function setMatches(int $matches): void
    {
        $this->matches = $matches;
    }

}