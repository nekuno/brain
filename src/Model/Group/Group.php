<?php

namespace Model\Group;

use Everyman\Neo4j\Node;
use Model\Photo\GroupPhoto;
use Model\Filters\FilterUsers;

class Group implements \JsonSerializable
{
    protected $id;
    protected $name;
    protected $html;
    protected $location;
    protected $date;
    protected $usersCount;
    protected $createdBy;
    protected $imagePath;
    /**
     * @var GroupPhoto $photo
     */
    protected $photo;
    protected $filterUsers;
    protected $invitation;
    protected $popularContents;

    public static function createFromNode(Node $groupNode)
    {
        $group = new static();
        $group->setId($groupNode->getId());

        if ($name = $groupNode->getProperty('name')){
            $group->setName($name);
        }
        // Not used for now, and might be too long
//        if ($name = $groupNode->getProperty('html')){
//            $group->setHtml($name);
//        }

        return $group;
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
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param mixed $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * @return array
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param array $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getUsersCount()
    {
        return $this->usersCount;
    }

    /**
     * @param mixed $usersCount
     */
    public function setUsersCount($usersCount)
    {
        $this->usersCount = $usersCount;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return mixed
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * @param mixed $imagePath
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * @return mixed
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param GroupPhoto $photo
     */
    public function setPhoto(GroupPhoto $photo)
    {
        $this->photo = $photo;
    }

    /**
     * @return mixed
     */
    public function getFilterUsers()
    {
        return $this->filterUsers;
    }

    /**
     * @param mixed $filterUsers
     */
    public function setFilterUsers(FilterUsers $filterUsers)
    {
        $this->filterUsers = $filterUsers;
    }

    /**
     * @return mixed
     */
    public function getInvitation()
    {
        return $this->invitation;
    }

    /**
     * @param mixed $invitation
     */
    public function setInvitation($invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * @return mixed
     */
    public function getPopularContents()
    {
        return $this->popularContents;
    }

    /**
     * @param mixed $popularContents
     */
    public function setPopularContents($popularContents)
    {
        $this->popularContents = $popularContents;
    }

    function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'html' => $this->getHtml(),
            'location' => $this->getLocation(),
            'date' => $this->getDate(),
            'usersCount' => $this->getUsersCount(),
            'createdBy' => $this->getCreatedBy(),
            'filter' => $this->getFilterUsers(),
            'invitation' => $this->getInvitation(),
            'image_path' => $this->getImagePath(),
            'photo' => $this->getPhoto() ? $this->getPhoto()->jsonSerialize() : null,
            'popularContents' => $this->getPopularContents(),
        );
    }

}