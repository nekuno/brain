<?php

namespace Service\Consistency\ConsistencyErrors;

class RelationshipOtherLabelConsistencyError extends ConsistencyError
{
    const NAME = 'Relationship other label';

    protected $type;
    protected $relationshipId;
    protected $otherNodeLabel;
    protected $availableLabels = array();

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

    /**
     * @return mixed
     */
    public function getOtherNodeLabel()
    {
        return $this->otherNodeLabel;
    }

    /**
     * @param mixed $otherNodeLabel
     */
    public function setOtherNodeLabel($otherNodeLabel)
    {
        $this->otherNodeLabel = $otherNodeLabel;
    }

    /**
     * @return array
     */
    public function getAvailableLabels()
    {
        return $this->availableLabels;
    }

    /**
     * @param array $availableLabels
     */
    public function setAvailableLabels($availableLabels)
    {
        $this->availableLabels = $availableLabels;
    }

    public function getMessage()
    {
        return sprintf('Label %s desired on node linked by relationship type %s not present', json_encode($this->otherNodeLabel), $this->type);
    }


}