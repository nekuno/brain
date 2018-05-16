<?php

namespace Model\Affinity;

class Affinity implements \JsonSerializable
{
    protected $affinity = 0;

    protected $updated = 0;

    /**
     * @return mixed
     */
    public function getAffinity()
    {
        return $this->affinity;
    }

    /**
     * @param mixed $affinity
     */
    public function setAffinity($affinity)
    {
        $this->affinity = $affinity;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param mixed $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function jsonSerialize()
    {
        return array(
            'affinity' => $this->affinity,
            'updated' => $this->updated,
        );
    }

}