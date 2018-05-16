<?php

namespace Model\Recommendation;

use Model\Photo\ProfilePhoto;

class UserRecommendation implements \JsonSerializable
{
    protected $id;
    protected $username;
    protected $slug;
    /**
     * @var ProfilePhoto
     */
    protected $photo;
    protected $matching;
    protected $similarity;
    protected $age;
    protected $location;
    protected $like;
    protected $profile;
    protected $topLinks = array();
    protected $sharedLinks;

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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return ProfilePhoto
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param ProfilePhoto $photo
     */
    public function setPhoto(ProfilePhoto $photo)
    {
        $this->photo = $photo;
    }

    /**
     * @return mixed
     */
    public function getMatching()
    {
        return $this->matching;
    }

    /**
     * @param mixed $matching
     */
    public function setMatching($matching)
    {
        $this->matching = $matching;
    }

    /**
     * @return mixed
     */
    public function getSimilarity()
    {
        return $this->similarity;
    }

    /**
     * @param mixed $similarity
     */
    public function setSimilarity($similarity)
    {
        $this->similarity = $similarity;
    }

    /**
     * @return mixed
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * @param mixed $age
     */
    public function setAge($age)
    {
        $this->age = $age;
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
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return mixed
     */
    public function getLike()
    {
        return $this->like;
    }

    /**
     * @param mixed $like
     */
    public function setLike($like)
    {
        $this->like = $like;
    }

    /**
     * @return mixed $profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param mixed $profile
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * @return array
     */
    public function getTopLinks()
    {
        return $this->topLinks;
    }

    /**
     * @param array $topLinks
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
            'username' => $this->getUsername(),
            'slug' => $this->getSlug(),
            'photo' => $this->getPhoto(),
            'matching' => $this->getMatching(),
            'similarity' => $this->getSimilarity(),
            'age' => $this->getAge(),
            'location' => $this->getLocation(),
            'like' => $this->getLike(),
            'profile' => $this->getProfile(),
            'topLinks' => $this->getTopLinks(),
            'sharedLinks' => $this->getSharedLinks(),
        );
    }
}