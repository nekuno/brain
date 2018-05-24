<?php

namespace Service\Consistency\ConsistencyErrors;

class ReverseRelationshipConsistencyError extends ConsistencyError
{
    const NAME = 'Reverse Relationship';

    protected $relationshipId;

    /**
     * ReverseRelationshipConsistencyError constructor.
     * @param $relationshipId
     */
    public function __construct($relationshipId)
    {
        $this->relationshipId = $relationshipId;
    }

    /**
     * @return mixed
     */
    public function getRelationshipId()
    {
        return $this->relationshipId;
    }

    /**
     * @param mixed $relationshipId
     */
    public function setRelationshipId($relationshipId)
    {
        $this->relationshipId = $relationshipId;
    }

    public function getMessage()
    {
        return sprintf('Direction of relationship %d is not correct', $this->relationshipId);
    }

}