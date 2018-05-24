<?php

namespace Service\Consistency\ConsistencyErrors;

class RelationshipAmountConsistencyError extends ConsistencyError
{
    const NAME = 'Relationship amount';

    protected $currentAmount;
    protected $minimum;
    protected $maximum;
    protected $type;

    /**
     * @return mixed
     */
    public function getCurrentAmount()
    {
        return $this->currentAmount;
    }

    /**
     * @param mixed $currentAmount
     */
    public function setCurrentAmount($currentAmount)
    {
        $this->currentAmount = $currentAmount;
    }

    /**
     * @return mixed
     */
    public function getMinimum()
    {
        return $this->minimum;
    }

    /**
     * @param mixed $minimum
     */
    public function setMinimum($minimum)
    {
        $this->minimum = $minimum;
    }

    /**
     * @return mixed
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * @param mixed $maximum
     */
    public function setMaximum($maximum)
    {
        $this->maximum = $maximum;
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

    public function getMessage()
    {
        return sprintf('Amount of relationships %d is inconsistent with minimum %d and maximum %d', $this->currentAmount, $this->minimum, $this->maximum);
    }


}