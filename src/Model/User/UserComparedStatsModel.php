<?php

namespace Model\User;


class UserComparedStatsModel
{
    protected $groupsBelonged;

    protected $userResourceOwners;

    protected $otherUserResourceOwners;

    function __construct($groupsBelonged,
                         $userResourceOwners,
                         $otherUserResourceOwners)
    {
        $this->groupsBelonged = $groupsBelonged;
        $this->userResourceOwners = $userResourceOwners;
        $this->otherUserResourceOwners = $otherUserResourceOwners;
    }

    /**
     * @param mixed $groupsBelonged
     */
    public function setGroupsBelonged($groupsBelonged)
    {
        $this->groupsBelonged = $groupsBelonged;
    }

    /**
     * @return mixed
     */
    public function getGroupsBelonged()
    {
        return $this->groupsBelonged;
    }

    /**
     * @param mixed $otherUserResourceOwners
     */
    public function setOtherUserResourceOwners($otherUserResourceOwners)
    {
        $this->otherUserResourceOwners = $otherUserResourceOwners;
    }

    /**
     * @return mixed
     */
    public function getOtherUserResourceOwners()
    {
        return $this->otherUserResourceOwners;
    }

    /**
     * @param mixed $userResourceOwners
     */
    public function setUserResourceOwners($userResourceOwners)
    {
        $this->userResourceOwners = $userResourceOwners;
    }

    /**
     * @return mixed
     */
    public function getUserResourceOwners()
    {
        return $this->userResourceOwners;
    }

    public function toArray(){
        return array('groupsBelonged' => $this->groupsBelonged,
                     'otherUserResourceOwners' => $this->otherUserResourceOwners,
                     'userResourceOwners' => $this->userResourceOwners,
        );
    }
}