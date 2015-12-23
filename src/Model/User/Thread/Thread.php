<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 4/12/15
 * Time: 11:07
 */

namespace Model\User\Thread;


class Thread implements \JsonSerializable
{

    protected $id;

    protected $name;

    protected $cached;

    protected $totalResults;


    function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
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
    public function setId($id)
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

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCached()
    {
        return $this->cached;
    }

    /**
     * @param mixed $cached
     */
    public function setCached($cached)
    {
        $this->cached = $cached;
    }

    /**
     * @return mixed
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * @param mixed $totalResults
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'cached' => $this->getCached(),
            'totalResults' => $this->getTotalResults(),
        );
    }


}