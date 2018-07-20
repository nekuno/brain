<?php

namespace Model\Proposal;

use Model\Proposal\ProposalFields\ProposalFieldInterface;

class Proposal
{
    protected $id;

    protected $name;

    /** @var ProposalFieldInterface[] */
    protected $fields = array();

    /**
     * Proposal constructor.
     * @param $name
     * @param ProposalFieldInterface[] $fields
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
     * @return ProposalFieldInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param ProposalFieldInterface[] $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
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

}