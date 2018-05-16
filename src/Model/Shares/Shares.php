<?php

namespace Model\Shares;

class Shares implements \JsonSerializable
{
    protected $id;

    protected $topLinks = array();

    protected $sharedLinks = 0;

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
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTopLinks()
    {
        return $this->topLinks;
    }

    /**
     * @param mixed $topLinks
     */
    public function setTopLinks($topLinks)
    {
        $this->topLinks = $topLinks;
    }

    /**
     * @return mixed
     */
    public function getSharedLinks()
    {
        return $this->sharedLinks;
    }

    /**
     * @param mixed $sharedLinks
     */
    public function setSharedLinks($sharedLinks)
    {
        $this->sharedLinks = $sharedLinks;
    }

    public function toArray()
    {
        return array(
            'topLinks' => $this->topLinks,
            'sharedLinks' => $this->sharedLinks,
        );
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}