<?php

namespace Model\Stats;


class UserComparedStats
{
    protected $groupsBelonged;

    protected $userResourceOwners;

    protected $otherUserResourceOwners;

    protected $commonContent;

    protected $commonAnswers;

    function __construct($groupsBelonged,
                         $userResourceOwners,
                         $otherUserResourceOwners,
                         $commonContent,
                         $commonAnswers)
    {
        $this->groupsBelonged = $groupsBelonged;
        $this->userResourceOwners = $userResourceOwners;
        $this->otherUserResourceOwners = $otherUserResourceOwners;
        $this->commonContent = $commonContent;
        $this->commonAnswers = $commonAnswers;
    }

    /**
     * @return mixed
     */
    public function getCommonContent()
    {
        return $this->commonContent;
    }

    /**
     * @param mixed $commonContent
     */
    public function setCommonContent($commonContent)
    {
        $this->commonContent = $commonContent;
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
                     'commonContent' => $this->commonContent,
                     'commonAnswers' => $this->commonAnswers,
        );
    }
}