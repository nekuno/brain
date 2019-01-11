<?php

namespace Model\Profile;

class OwnProfileData implements \JsonSerializable
{
    protected $userName;

    protected $photos = array();

    protected $naturalProfile = array();

    protected $location;

    protected $birthday = '1970-01-01';

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param mixed $userName
     */
    public function setUserName($userName): void
    {
        $this->userName = $userName;
    }

    /**
     * @return array
     */
    public function getPhotos(): array
    {
        return $this->photos;
    }

    /**
     * @param array $photos
     */
    public function setPhotos(array $photos): void
    {
        $this->photos = $photos;
    }

    /**
     * @return array
     */
    public function getNaturalProfile(): array
    {
        return $this->naturalProfile;
    }

    /**
     * @param array $naturalProfile
     */
    public function setNaturalProfile(array $naturalProfile): void
    {
        $this->naturalProfile = $naturalProfile;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     */
    public function setLocation($location): void
    {
        $this->location = $location;
    }

    /**
     * @return mixed
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param mixed $birthday
     */
    public function setBirthday($birthday): void
    {
        $this->birthday = $birthday;
    }


    public function jsonSerialize()
    {
        return array(
            'username' => $this->userName,
            'photos' => $this->photos,
            'naturalProfile' => $this->naturalProfile,
            'birthday' => $this->birthday,
            'location' => $this->location,
        );
    }
}