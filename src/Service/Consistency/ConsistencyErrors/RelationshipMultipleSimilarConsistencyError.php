<?php

namespace Service\Consistency\ConsistencyErrors;

class RelationshipMultipleSimilarConsistencyError extends RelationshipAmountConsistencyError
{
    protected $otherNodeId;

    /**
     * @return mixed
     */
    public function getOtherNodeId()
    {
        return $this->otherNodeId;
    }

    /**
     * @param mixed $otherNodeId
     */
    public function setOtherNodeId($otherNodeId)
    {
        $this->otherNodeId = $otherNodeId;
    }

    public function getMessage()
    {
        return sprintf('Amount of relationships of type %s between %d and %d is %d', $this->type, $this->nodeId, $this->otherNodeId, $this->currentAmount);
    }

}