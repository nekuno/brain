<?php

namespace Model\Proposal\ProposalFields;

interface ProposalFieldInterface
{
    /**
     * Add new variable to $variables to be used with WITH later
     * Return partial query to add
     * @param array $variables Already available variables from the earlier query, ready to be used with WITH
     * @return mixed
     */
    public function addInformation(array &$variables);

    public function getSaveQuery(array $variables);

    public function getData();

    public function setName($name);
}