<?php

namespace Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\PrePersist;

/**
 * @Entity(repositoryClass="ChatMessageRepository")
 * @Table(name="chat_message")
 * @HasLifecycleCallbacks()
 */
class ChatMessage
{
    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @Column(name="user_from", type="integer", nullable=false)
     */
    protected $userFrom;

    /**
     * @Column(name="user_to", type="integer", nullable=false)
     */
    protected $userTo;

    /**
     * @Column(name="text", type="string", length=3000, nullable=false)
     */
    protected $text;

    /**
     * @Column(name="createdAt", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @Column(name="readed", type="integer", nullable=false)
     */
    protected $readed;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return integer
     */
    public function getUserFrom()
    {
        return $this->userFrom;
    }

    /**
     * @param integer $userFrom
     */
    public function setUserFrom($userFrom)
    {
        $this->userFrom = $userFrom;
    }

    /**
     * @return integer
     */
    public function getUserTo()
    {
        return $this->userTo;
    }

    /**
     * @param integer $userTo
     */
    public function setUserTo($userTo)
    {
        $this->userTo = $userTo;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return integer
     */
    public function getReaded()
    {
        return $this->readed;
    }

    /**
     * @param integer $readed
     */
    public function setReaded($readed)
    {
        $this->readed = $readed;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @PrePersist
     */
    public function setCreatedAt()
    {
        $this->createdAt = new \DateTime();
    }
}
