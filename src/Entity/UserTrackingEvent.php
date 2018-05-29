<?php

namespace Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Entity(repositoryClass="UserTrackingEventRepository")
 * @Table(name="user_tracking_event")
 * @HasLifecycleCallbacks()
 */
class UserTrackingEvent
{
    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @Column(name="action", type="string", nullable=true)
     */
    protected $action;

    /**
     * @Column(name="category", type="string", nullable=true)
     */
    protected $category;

    /**
     * @Column(name="tag", type="string", nullable=true)
     */
    protected $tag;

    /**
     * @Column(name="user_id", type="integer", nullable=true)
     */
    protected $userId;

    /**
     * @Column(name="data", type="text", nullable=true)
     */
    protected $data;

    /**
     * @Column(name="created_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function toArray()
    {
        return array(
            'userId' => $this->userId,
            'action' => $this->action,
            'category' => $this->category,
            'tag' => $this->tag,
            'data' => $this->data,
            'createdAt' => $this->createdAt,
        );
    }
}
