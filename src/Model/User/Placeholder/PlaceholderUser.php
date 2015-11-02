<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 30/10/15
 * Time: 12:32
 */

namespace Model\User\Placeholder;



class PlaceholderUser
{

    protected $id;

    protected $createdAt;

    function __construct($id)
    {
        $this->id = $id;
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }


}