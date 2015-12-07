<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 4/12/15
 * Time: 11:41
 */

namespace Model\User\Thread;


class UsersThread extends Thread
{
    protected $profileFilters;
    protected $userFilters;

    /**
     * @return array
     */
    public function getProfileFilters()
    {
        return $this->profileFilters;
    }

    /**
     * @param array $profileFilters
     */
    public function setProfileFilters($profileFilters)
    {
        $this->profileFilters = $profileFilters;
    }

    /**
     * @return array
     */
    public function getUserFilters()
    {
        return $this->userFilters;
    }

    /**
     * @param array $userFilters
     */
    public function setUserFilters($userFilters)
    {
        $this->userFilters = $userFilters;
    }

    function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $array += array(
            'category' => ThreadManager::LABEL_THREAD_USERS,
            'profileFilters' => $this->getProfileFilters(),
            'userFilters' => $this->getUserFilters(),
        );

        return $array;
    }
}